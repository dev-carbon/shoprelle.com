<?php

namespace App\Chatbot;

use App\DataTransferObjects\PendingAttachmentData;
use App\Enums\Marketplace;
use App\Exceptions\ConversationException;
use App\Models\Review;

/**
 * The Shoprelle conversation, as a pure state machine.
 *
 * The engine never touches the database, the session or the filesystem: given a
 * state and an answer it returns the next state. That is what makes the same
 * flow reusable from a web page, a Telegram or WhatsApp webhook, and a test.
 *
 * Steps that need data the engine cannot reach (tracking a request, listing a
 * customer's requests) are resolved by {@see ConversationManager}, which hands
 * the result back through presentTracking() and presentOrders().
 *
 * To add a step: add a case to {@see Step}, describe it in prompt(), options()
 * and placeholder(), validate it in validate(), store it in apply(), and place
 * it in next(). Nothing outside this class needs to change.
 */
class ChatbotEngine
{
    public const CONFIRM = 'confirm';

    public const RESTART = 'restart';

    public const MENU = 'menu';

    private const YES = 'yes';

    private const NO = 'no';

    public function start(Channel $channel = Channel::Web): ConversationState
    {
        $state = ConversationState::start($channel);

        $state->pushBotMessage(
            'Bonjour 👋 Bienvenue chez Shoprelle. Je suis votre assistant : vous trouvez le produit, nous nous occupons du reste.'
        );
        $state->pushBotMessage($this->prompt($state));

        return $state;
    }

    /**
     * Apply a customer answer and move the conversation forward.
     *
     * An invalid answer is not an error condition: the bot explains the problem
     * and asks again, exactly as a human operator would.
     */
    public function handle(ConversationState $state, string $input): ConversationState
    {
        $input = trim($input);
        $step = $state->step;

        if ($step === Step::Completed) {
            return $state;
        }

        $state->pushCustomerMessage($this->humanize($step, $input));

        if ($input === '' && $step->isOptional()) {
            return $this->advance($state);
        }

        $error = $this->validate($step, $input, $state);

        if ($error !== null) {
            $state->pushBotMessage($error);

            return $state;
        }

        $this->apply($state, $step, $input);

        return $this->advance($state);
    }

    /**
     * Open a new request straight from a product link.
     *
     * The landing page asks for the one thing the visitor already has in their
     * clipboard, so the conversation starts three questions in: the platform is
     * read off the host, the link is stored, and the first thing actually asked
     * is the colour. A host no case claims is not an error — it becomes "another
     * site", which is exactly what the menu's own last option is for.
     */
    public function startFromLink(ConversationState $state, string $url): ConversationState
    {
        $marketplace = Marketplace::detect($url) ?? Marketplace::Other;

        $state->intent = Intent::NewOrder;
        $state->step = Step::ProductUrl;
        $state->draft = [
            'marketplace' => $marketplace->value,
            'product_url' => $url,
            'attachments' => [],
        ];

        $state->pushCustomerMessage($url);
        $state->pushBotMessage(sprintf(
            $marketplace === Marketplace::Other
                ? 'Lien reçu 👍 Nous achetons aussi en dehors des grandes plateformes.'
                : '%s détecté 👍',
            $marketplace->label(),
        ));

        return $this->advance($state);
    }

    /**
     * Validate an answer without applying it, for the steps the manager has to
     * resolve itself.
     */
    public function validateAnswer(ConversationState $state, string $input): ?string
    {
        return $this->validate($state->step, trim($input), $state);
    }

    /**
     * Record a rejected answer without advancing.
     */
    public function reject(ConversationState $state, string $input, string $error): ConversationState
    {
        $state->pushCustomerMessage($this->humanize($state->step, trim($input)));
        $state->pushBotMessage($error);

        return $state;
    }

    /**
     * Move past an optional step without answering it.
     */
    public function skip(ConversationState $state): ConversationState
    {
        if (! $state->step->isOptional()) {
            return $state;
        }

        $state->pushCustomerMessage('Passer');

        return $this->advance($state);
    }

    /**
     * Record a screenshot the customer just uploaded and stay on the step so
     * more can be added.
     *
     * @throws ConversationException when the per-item limit is reached
     */
    public function attach(ConversationState $state, PendingAttachmentData $attachment): ConversationState
    {
        $max = (int) config('shoprelle.attachments.max_per_item');
        $attachments = $state->draft['attachments'] ?? [];

        if (count($attachments) >= $max) {
            throw ConversationException::tooManyAttachments($max);
        }

        $attachments[] = $attachment->toArray();
        $state->draft['attachments'] = $attachments;

        $state->pushCustomerMessage('📎 '.$attachment->originalName);
        $state->pushBotMessage(
            count($attachments) >= $max
                ? 'Capture reçue. Vous avez atteint le nombre maximum de captures pour ce produit.'
                : 'Capture reçue ✅ Vous pouvez en ajouter une autre ou continuer.'
        );

        return $state;
    }

    /**
     * Close the request flow once the request has been persisted.
     *
     * The access code is passed only on a customer's very first request, since
     * it is stored hashed and cannot be read back afterwards. That is the whole
     * reason it is announced so plainly here: this message is the only time
     * anyone will ever see it.
     */
    public function complete(ConversationState $state, string $reference, ?string $accessCode = null): ConversationState
    {
        $state->reference = $reference;
        $state->step = Step::Completed;
        $state->draft = [];
        // Submitting a request is what ties this conversation to a number.
        // `returnToMenu` deliberately leaves it alone, so anything asked
        // later in the same session — a review, for one — knows who is
        // speaking without asking again.
        $state->knownPhone = $state->customer['phone'] ?? null;

        $state->pushBotMessage(sprintf(
            'Merci ! Votre demande %s a bien été enregistrée. Notre équipe l\'étudie et vous recontacte au %s avec le devis complet (produits + livraison).',
            $reference,
            $state->customer['phone'] ?? 'numéro fourni',
        ));
        $state->pushBotMessage('Conservez cette référence : elle vous permet de suivre votre demande à tout moment.');

        if ($accessCode !== null) {
            $state->pushBotMessage(sprintf(
                "🔐 Voici votre code d'accès : %s\nAvec votre numéro, il ouvre la liste de toutes vos demandes et vos devis sur %s. Notez-le maintenant : nous ne pouvons pas vous le renvoyer.",
                $accessCode,
                route('orders.index'),
            ));
        }

        return $state;
    }

    /**
     * Show the outcome of a tracking request, then return to the menu.
     *
     * @param  array{reference: string, status_label: string, item_count: int, created_at: string, quote: string|null}|null  $tracking
     */
    public function presentTracking(ConversationState $state, string $input, ?array $tracking): ConversationState
    {
        $state->pushCustomerMessage(trim($input));

        if ($tracking === null) {
            $state->pushBotMessage(
                'Aucune demande ne correspond à cette référence et à ce numéro. Vérifiez la référence reçue lors de votre commande.'
            );
        } else {
            $state->pushBotMessage(sprintf(
                "📦 Demande %s\nStatut : %s\nProduits : %d\nCréée le : %s%s",
                $tracking['reference'],
                $tracking['status_label'],
                $tracking['item_count'],
                $tracking['created_at'],
                $tracking['quote'] !== null ? "\nDevis : ".$tracking['quote'] : '',
            ));
        }

        return $this->returnToMenu($state);
    }

    /**
     * List the requests attached to a phone number, then return to the menu.
     *
     * @param  list<array{reference: string, status_label: string, created_at: string}>  $orders
     */
    public function presentOrders(ConversationState $state, string $input, array $orders): ConversationState
    {
        $state->pushCustomerMessage(trim($input));

        if ($orders === []) {
            // Deliberately the same answer whether the code was wrong, the
            // number unknown, or the account simply empty. Distinguishing them
            // would let somebody confirm which numbers are customers.
            $state->pushBotMessage(
                'Aucune demande ne correspond à ce numéro et à ce code. Vérifiez le code reçu lors de votre première demande.'
            );

            return $this->returnToMenu($state);
        }

        $lines = array_map(
            fn (array $order): string => sprintf(
                '• %s — %s (%s)',
                $order['reference'],
                $order['status_label'],
                $order['created_at'],
            ),
            $orders,
        );

        $state->pushBotMessage("📋 Vos demandes :\n".implode("\n", $lines));

        return $this->returnToMenu($state);
    }

    /**
     * Thank the customer for a review that has just been stored, then return to
     * the menu.
     */
    public function presentReview(ConversationState $state, string $input): ConversationState
    {
        $state->pushCustomerMessage(trim($input) === '' ? 'Passer' : trim($input));
        $state->pushBotMessage(
            'Merci beaucoup pour votre avis 🙏 Il est transmis à notre équipe et nous aide à progresser.'
        );

        return $this->returnToMenu($state);
    }

    /**
     * Ouvrir la conversation directement sur « nous écrire ».
     *
     * C'est le pendant de {@see startFromLink} pour les questions : on arrive
     * d'une page qui sait déjà de quoi on veut parler — sa demande — et
     * redemander la référence à quelqu'un qui vient de la regarder serait lui
     * faire retaper ce qu'il a sous les yeux.
     *
     * Ce qui était en cours est abandonné, comme pour un lien collé : c'est ce
     * que demande quelqu'un qui clique « poser une question ».
     */
    public function startContact(ConversationState $state, ?string $reference = null): ConversationState
    {
        $state->intent = Intent::ContactUs;
        $state->step = Step::ContactMessage;
        $state->draft = [];
        $state->tracking = $reference === null ? [] : ['reference' => $reference];

        $state->pushBotMessage($reference === null
            ? '✍️ Je vous écoute — dites-nous tout, je transmets à l\'équipe.'
            : sprintf('✍️ Au sujet de la demande %s : que voulez-vous nous dire ? Je transmets à l\'équipe avec la référence.', $reference)
        );

        return $state;
    }

    /**
     * Confirmer un message transmis, puis revenir au menu.
     *
     * Le texte dit ce qui va se passer, et il change selon qu'un moyen de
     * rappel a été laissé ou non : promettre une réponse à quelqu'un qu'on ne
     * peut pas joindre serait la seule chose malhonnête que cet écran puisse
     * faire.
     */
    public function presentContactMessage(ConversationState $state, string $input): ConversationState
    {
        $replyTo = trim($input);

        $state->pushCustomerMessage($replyTo === '' ? 'Passer' : $replyTo);
        $state->pushBotMessage($replyTo === ''
            ? 'C\'est transmis à notre équipe 🙏 Sans moyen de vous joindre nous ne pourrons pas répondre, mais votre message est bien arrivé — revenez quand vous voulez.'
            : sprintf('C\'est transmis à notre équipe 🙏 Nous vous répondons sur %s, %s.', $replyTo, config('shoprelle.contact.response_time'))
        );

        return $this->returnToMenu($state);
    }

    /**
     * The recap as plain text, for channels that cannot render a card.
     *
     * The web shows a structured summary component; Telegram and any future
     * messaging channel show this instead, from the very same state.
     */
    public function summaryText(ConversationState $state): string
    {
        $summary = $this->summarize($state);
        $lines = ['📋 Récapitulatif de votre demande', ''];

        foreach ($summary['items'] as $index => $item) {
            $lines[] = sprintf('%d. %s', $index + 1, $item['marketplace_label']);
            $lines[] = '   '.$item['product_url'];

            $details = array_filter([
                'Quantité : '.$item['quantity'],
                $item['color'] !== null ? 'Couleur : '.$item['color'] : null,
                $item['size'] !== null ? 'Taille : '.$item['size'] : null,
                $item['declared_price'] !== null
                    ? 'Prix affiché : '.$item['declared_price'].' '.($item['declared_currency'] ?? '')
                    : null,
            ]);

            $lines[] = '   '.implode(' · ', $details);

            if ($item['comment'] !== null) {
                $lines[] = '   Note : '.$item['comment'];
            }

            if ($item['attachment_count'] > 0) {
                $lines[] = sprintf('   %d capture(s) jointe(s)', $item['attachment_count']);
            }

            $lines[] = '';
        }

        $customer = $summary['customer'];

        $lines[] = '🚚 Livraison';
        $lines[] = 'Nom : '.$customer['full_name'];
        $lines[] = 'Téléphone : '.$customer['phone'];
        $lines[] = 'Destination : '.$customer['city'].', '.$customer['country_label'];

        if ($customer['email'] !== null) {
            $lines[] = 'Email : '.$customer['email'];
        }

        return implode("\n", $lines);
    }

    public function isConfirmation(ConversationState $state, string $input): bool
    {
        return $state->step === Step::Summary && trim($input) === self::CONFIRM;
    }

    /**
     * The descriptor a channel needs to render the current step.
     *
     * @return array{
     *     step: string,
     *     input_type: string,
     *     optional: bool,
     *     placeholder: string|null,
     *     max_length: int|null,
     *     options: list<array{value: string, label: string}>,
     *     item_number: int,
     * }
     */
    public function describe(ConversationState $state): array
    {
        return [
            'step' => $state->step->value,
            'input_type' => $state->step->inputType()->value,
            'optional' => $state->step->isOptional(),
            'placeholder' => $this->placeholder($state->step),
            'max_length' => $this->maxLength($state->step),
            'options' => $this->options($state->step),
            'item_number' => count($state->items) + 1,
            'progress' => $this->progress($state),
        ];
    }

    /**
     * Where the customer stands in the request flow.
     *
     * Individual steps are grouped into a handful of milestones: fifteen ticks
     * would feel endless, six reads as a short errand. Returns null outside the
     * request flow, where there is no journey to show.
     *
     * @return array{current: int, total: int, milestones: list<array{label: string, state: string}>}|null
     */
    public function progress(ConversationState $state): ?array
    {
        $position = null;
        $milestones = [];

        foreach ($this->milestones() as $index => [$label, $steps]) {
            if (in_array($state->step, $steps, strict: true)) {
                $position = $index;
            }

            $milestones[] = ['label' => $label, 'state' => 'todo'];
        }

        if ($position === null) {
            return null;
        }

        foreach ($milestones as $index => $milestone) {
            $milestones[$index]['state'] = match (true) {
                $index < $position => 'done',
                $index === $position => 'current',
                default => 'todo',
            };
        }

        return [
            'current' => $position + 1,
            'total' => count($milestones),
            'milestones' => $milestones,
        ];
    }

    /**
     * The request flow, grouped into what a customer would call a stage.
     *
     * @return list<array{0: string, 1: list<Step>}>
     */
    private function milestones(): array
    {
        return [
            ['Plateforme', [Step::Marketplace]],
            ['Produit', [Step::ProductUrl]],
            ['Détails', [
                Step::Color,
                Step::Size,
                Step::Quantity,
                Step::DeclaredPrice,
                Step::Screenshot,
                Step::ItemComment,
                Step::MoreItems,
            ]],
            ['Livraison', [Step::Country, Step::City]],
            ['Contact', [Step::Phone, Step::FullName, Step::Email]],
            ['Confirmation', [Step::Summary]],
        ];
    }

    /**
     * The recap shown before the customer confirms.
     *
     * @return array{items: list<array<string, mixed>>, customer: array<string, mixed>}
     */
    public function summarize(ConversationState $state): array
    {
        return [
            'items' => array_map(function (array $item): array {
                $marketplace = Marketplace::from($item['marketplace']);

                return [
                    'marketplace' => $marketplace->value,
                    'marketplace_label' => $marketplace->label(),
                    'product_url' => $item['product_url'],
                    'quantity' => $item['quantity'] ?? 1,
                    'color' => $item['color'] ?? null,
                    'size' => $item['size'] ?? null,
                    'declared_price' => $item['declared_price'] ?? null,
                    'declared_currency' => $item['declared_currency'] ?? null,
                    'comment' => $item['comment'] ?? null,
                    'attachment_count' => count($item['attachments'] ?? []),
                ];
            }, $state->items),
            'customer' => [
                'full_name' => trim(($state->customer['first_name'] ?? '').' '.($state->customer['last_name'] ?? '')),
                'phone' => $state->customer['phone'] ?? null,
                'email' => $state->customer['email'] ?? null,
                'country' => $state->customer['country'] ?? null,
                'country_label' => isset($state->customer['country'])
                    ? config('shoprelle.countries.'.$state->customer['country'])
                    : null,
                'city' => $state->customer['city'] ?? null,
            ],
        ];
    }

    /**
     * Send the customer back to the welcome menu, keeping the transcript.
     */
    public function returnToMenu(ConversationState $state): ConversationState
    {
        $state->step = Step::Menu;
        $state->intent = null;
        $state->draft = [];
        $state->tracking = [];
        $state->pushBotMessage($this->prompt($state));

        return $state;
    }

    /**
     * Move to the next step and ask its question.
     */
    private function advance(ConversationState $state): ConversationState
    {
        $next = $this->next($state);

        // Leaving the item block closes the product line being described, so the
        // "product n°X saved" prompt and the summary see a consistent list.
        if ($state->step === Step::ItemComment && $next === Step::MoreItems) {
            $state->commitDraft();
        }

        $acknowledgement = $this->acknowledgement($state->step);

        if ($acknowledgement !== null) {
            $state->pushBotMessage($acknowledgement);
        }

        $state->step = $next;
        $state->pushBotMessage($this->prompt($state));

        return $state;
    }

    /**
     * A short word of acknowledgement before the next question.
     *
     * Only on the answers that cost the customer something to give: a line
     * after every single step would double the transcript and read as a tic
     * rather than as attention. The steps that already confirm themselves —
     * an upload, a saved product, the recap — say nothing more here.
     */
    private function acknowledgement(Step $step): ?string
    {
        return match ($step) {
            Step::ProductUrl => 'Parfait, j\'ai le lien 👍',
            Step::DeclaredPrice => 'Merci, cela nous aide à préparer votre devis.',
            Step::ItemComment => 'C\'est noté, nous en tiendrons compte.',
            Step::Phone => 'Merci 👍',
            Step::FullName => 'Enchanté !',
            default => null,
        };
    }

    /**
     * The step that follows the current one.
     */
    private function next(ConversationState $state): Step
    {
        return match ($state->step) {
            Step::Menu => match ($state->intent) {
                Intent::NewOrder => Step::Marketplace,
                Intent::TrackOrder => Step::TrackReference,
                Intent::MyOrders => Step::MyOrdersPhone,
                Intent::LeaveReview => Step::ReviewRating,
                /*
                 * Déjà écrit au menu ? On ne redemande pas le message : il ne
                 * reste qu'à savoir comment répondre.
                 */
                Intent::ContactUs => isset($state->tracking['message'])
                    ? Step::ContactReply
                    : Step::ContactMessage,
                Intent::Help => Step::HelpTopic,
                null => Step::Menu,
            },
            // Answering a subject leaves the customer in the help menu; the
            // "back" option clears the intent, which is what ends the loop.
            Step::HelpTopic => $state->intent === Intent::Help ? Step::HelpTopic : Step::Menu,
            Step::Marketplace => Step::ProductUrl,
            Step::ProductUrl => Step::Color,
            Step::Color => Step::Size,
            Step::Size => Step::Quantity,
            Step::Quantity => Step::DeclaredPrice,
            Step::DeclaredPrice => Step::Screenshot,
            Step::Screenshot => Step::ItemComment,
            Step::ItemComment => Step::MoreItems,
            Step::MoreItems => $state->wantsMoreItems ? Step::Marketplace : Step::Country,
            Step::Country => Step::City,
            Step::City => Step::Phone,
            Step::Phone => Step::FullName,
            Step::FullName => Step::Email,
            Step::Email => Step::Summary,
            Step::TrackReference => Step::TrackPhone,
            Step::MyOrdersPhone => Step::MyOrdersCode,
            Step::ReviewRating => Step::ReviewComment,
            Step::ContactMessage => Step::ContactReply,
            // Resolved by the manager, which calls presentTracking, presentOrders
            // or presentReview.
            Step::TrackPhone, Step::MyOrdersCode, Step::ReviewComment,
            Step::ContactReply => Step::Menu,
            Step::Summary, Step::Completed => Step::Completed,
        };
    }

    /**
     * The question asked at the current step.
     */
    private function prompt(ConversationState $state): string
    {
        $number = count($state->items) + 1;
        $isFirstItem = $number === 1;

        return match ($state->step) {
            Step::Menu => 'Que souhaitez-vous faire ?',
            Step::HelpTopic => 'Sur quoi puis-je vous renseigner ?',
            Step::Marketplace => $isFirstItem
                ? '🌐 Sur quelle plateforme souhaitez-vous acheter ?'
                : sprintf('🌐 Produit n°%d — sur quelle plateforme se trouve-t-il ?', $number),
            Step::ProductUrl => '📎 Envoyez le lien du produit.',
            Step::Color => '🎨 Quelle couleur souhaitez-vous ? (facultatif)',
            Step::Size => '📏 Quelle taille ou pointure ? (facultatif)',
            Step::Quantity => '🔢 Quelle quantité souhaitez-vous ?',
            Step::DeclaredPrice => sprintf(
                '💶 Quel prix est affiché sur le site, en %s ? (facultatif, cela nous aide à préparer le devis)',
                config('shoprelle.declared_currency'),
            ),
            Step::Screenshot => '🖼️ Vous pouvez joindre une capture d\'écran du produit. (facultatif)',
            Step::ItemComment => '💬 Une précision sur ce produit ? (facultatif)',
            Step::MoreItems => sprintf(
                'Produit n°%d enregistré ✅ Souhaitez-vous ajouter un autre produit ?',
                count($state->items),
            ),
            Step::Country => '🚚 Passons à la livraison. Dans quel pays devons-nous livrer ?',
            Step::City => '🏙️ Dans quelle ville ?',
            Step::Phone => '📱 Quel est votre numéro de téléphone ? (c\'est par là que nous vous recontactons)',
            Step::FullName => '🙋 Quels sont vos nom et prénom ?',
            Step::Email => '✉️ Votre adresse email ? (facultatif)',
            Step::Summary => 'Voici le récapitulatif de votre demande. Tout est correct ?',
            Step::Completed => 'Votre demande a été transmise à notre équipe.',
            Step::TrackReference => '🔎 Indiquez la référence de votre demande (par exemple SHP-2607-4KJ9X2).',
            Step::TrackPhone => '📱 Confirmez le numéro de téléphone utilisé lors de la demande.',
            Step::MyOrdersPhone => '📱 Indiquez le numéro de téléphone associé à vos demandes.',
            Step::MyOrdersCode => "🔐 Indiquez votre code d'accès, celui reçu lors de votre première demande.",
            Step::ReviewRating => 'Merci de prendre le temps 🙏 Comment évaluez-vous votre expérience avec Shoprelle ?',
            Step::ReviewComment => "Souhaitez-vous ajouter un mot ? Dites-nous ce qui s'est bien passé, ou ce qui n'a pas été. (facultatif)",
            Step::ContactMessage => '✍️ Écrivez-nous : une question, une demande particulière, un souci. Je transmets à l\'équipe.',
            Step::ContactReply => 'Comment pouvons-nous vous répondre ? Un numéro ou une adresse email. (facultatif)',
        };
    }

    /**
     * How many characters a free-form step accepts.
     *
     * These are the same numbers validate() rejects on. Kept here rather than
     * hard-coded in the composer so a field can never let somebody type past a
     * limit only to be turned away when they send it.
     */
    private function maxLength(Step $step): ?int
    {
        return match ($step) {
            Step::Color, Step::Size => 60,
            Step::City => 80,
            Step::FullName => 120,
            Step::ItemComment, Step::ReviewComment => 500,
            Step::ContactMessage => 1500,
            Step::ProductUrl => 2048,
            Step::Phone, Step::TrackPhone, Step::MyOrdersPhone => 20,
            Step::TrackReference => 26,
            // Six characters, plus room for the separator and a stray space.
            Step::MyOrdersCode => 12,
            Step::Email => 254,
            default => null,
        };
    }

    /**
     * Hint text for free-form steps.
     */
    private function placeholder(Step $step): ?string
    {
        return match ($step) {
            Step::ProductUrl => 'https://…',
            Step::Color => 'Noir, rouge…',
            Step::Size => 'M, 42, Taille unique…',
            Step::Quantity => '1',
            Step::DeclaredPrice => '29.99',
            Step::ItemComment => 'Ex : modèle avec capuche, sans logo…',
            Step::City => 'Douala',
            Step::Phone, Step::TrackPhone, Step::MyOrdersPhone => '+237 6 XX XX XX XX',
            Step::FullName => 'Awa Ndiaye',
            Step::Email => 'vous@exemple.com',
            Step::TrackReference => 'SHP-2607-4KJ9X2',
            Step::MyOrdersCode => 'K4M-9PZ',
            Step::ReviewComment => 'Ex : livraison rapide, équipe à l\'écoute…',
            Step::ContactMessage => 'Ex : livrez-vous à Kribi ? Je cherche un produit introuvable en ligne…',
            Step::ContactReply => 'Ex : +237 6 XX XX XX XX ou vous@exemple.com',
            default => null,
        };
    }

    /**
     * The choices offered at the current step, if any.
     *
     * @return list<array{value: string, label: string}>
     */
    private function options(Step $step): array
    {
        return match ($step) {
            Step::Menu => Intent::options(),
            Step::HelpTopic => [
                ...HelpTopic::options(),
                ['value' => self::MENU, 'label' => '↩️ Retour au menu'],
            ],
            Step::Marketplace => Marketplace::options(),
            Step::Country => $this->countryOptions(),
            Step::MoreItems => [
                ['value' => self::YES, 'label' => 'Oui, ajouter un produit'],
                ['value' => self::NO, 'label' => 'Non, continuer'],
            ],
            Step::Summary => [
                ['value' => self::CONFIRM, 'label' => 'Confirmer ma demande'],
                ['value' => self::RESTART, 'label' => 'Tout recommencer'],
            ],
            Step::ReviewRating => $this->ratingOptions(),
            default => [],
        };
    }

    /**
     * The destination countries Shoprelle currently ships to.
     *
     * @return list<array{value: string, label: string}>
     */
    private function countryOptions(): array
    {
        $options = [];

        /** @var array<string, string> $countries */
        $countries = config('shoprelle.countries');

        foreach ($countries as $code => $name) {
            $options[] = ['value' => $code, 'label' => $name];
        }

        return $options;
    }

    /**
     * Turn a submitted value into the text shown in the transcript.
     */
    private function humanize(Step $step, string $input): string
    {
        foreach ($this->options($step) as $option) {
            if ($option['value'] === $input) {
                return $option['label'];
            }
        }

        return $input === '' ? 'Passer' : $input;
    }

    /**
     * Ce que le menu fait de ce qu'on lui donne.
     *
     * Un bouton donne une intention. Une phrase donne un message : on retient
     * l'intention « nous écrire » *et* le texte, pour que l'étape suivante soit
     * « comment vous répondre ? » plutôt que « écrivez-nous » à quelqu'un qui
     * vient de le faire.
     */
    private function applyMenu(ConversationState $state, string $input): void
    {
        $intent = Intent::tryFrom($input);

        if ($intent !== null) {
            $state->intent = $intent;

            return;
        }

        $state->intent = Intent::ContactUs;
        $state->tracking['message'] = trim($input);
    }

    /**
     * Validate an answer, returning the bot's reply when it is not acceptable.
     */
    private function validate(Step $step, string $input, ConversationState $state): ?string
    {
        return match ($step) {
            /*
             * Écrire au menu n'est plus une erreur.
             *
             * C'était « je n'ai pas bien saisi, choisissez une option » — une
             * réponse qui renvoie à des boutons quelqu'un qui vient justement
             * de formuler sa demande en toutes lettres. Un texte assez long
             * pour être une phrase est traité comme un message à l'équipe ; ce
             * qui est trop court pour en être une reste une erreur de frappe,
             * et rien ne serait transmis d'utile.
             */
            Step::Menu => Intent::tryFrom($input) === null && mb_strlen(trim($input)) < 5
                ? 'Je n\'ai pas bien saisi. Choisissez une option ci-dessous, ou écrivez-moi votre demande 🙂'
                : null,
            Step::HelpTopic => HelpTopic::tryFrom($input) === null && $input !== self::MENU
                ? 'Choisissez un sujet dans la liste.'
                : null,
            Step::Marketplace => Marketplace::tryFrom($input) === null
                ? 'Je ne connais pas encore ce site. Choisissez-en un dans la liste.'
                : null,
            Step::ProductUrl => $this->validateUrl($input, $state),
            Step::Color, Step::Size => mb_strlen($input) > 60
                ? 'C\'est un peu long — pouvez-vous rester sous 60 caractères ?'
                : null,
            Step::Quantity => $this->validateQuantity($input),
            Step::DeclaredPrice => $this->validatePrice($input),
            Step::ItemComment => mb_strlen($input) > 500
                ? 'Votre précision est un peu longue — pouvez-vous la résumer en 500 caractères ?'
                : null,
            Step::MoreItems => in_array($input, [self::YES, self::NO], strict: true)
                ? null
                : 'Répondez simplement « oui » ou « non ».',
            Step::Country => array_key_exists($input, config('shoprelle.countries'))
                ? null
                : 'Nous ne livrons pas encore dans ce pays. Choisissez-en un dans la liste.',
            // Wording for the remaining steps lives with their validators.
            Step::City => mb_strlen($input) < 2 || mb_strlen($input) > 80
                ? 'Je n\'ai pas reconnu cette ville. Vous pouvez la réécrire ?'
                : null,
            Step::Phone, Step::TrackPhone, Step::MyOrdersPhone => $this->validatePhone($input),
            Step::FullName => $this->validateFullName($input),
            Step::Email => filter_var($input, FILTER_VALIDATE_EMAIL) === false
                ? 'Cette adresse ne semble pas valide. Vous pouvez aussi passer cette étape.'
                : null,
            Step::TrackReference => $this->validateReference($input),
            Step::MyOrdersCode => $this->validateAccessCode($input),
            Step::ReviewRating => $this->validateRating($input),
            Step::ContactMessage => match (true) {
                mb_strlen(trim($input)) < 5 => 'Dites-m\'en un peu plus, que je puisse transmettre quelque chose d\'utile.',
                mb_strlen($input) > 1500 => 'Votre message est un peu long — pouvez-vous le résumer en 1500 caractères ?',
                default => null,
            },
            Step::ContactReply => mb_strlen($input) > 120
                ? 'Ce moyen de contact est un peu long — un numéro ou une adresse suffit.'
                : null,
            Step::ReviewComment => mb_strlen($input) > 500
                ? 'Votre avis est un peu long — pouvez-vous le résumer en 500 caractères ?'
                : null,
            Step::Summary => in_array($input, [self::CONFIRM, self::RESTART], strict: true)
                ? null
                : 'Confirmez votre demande ou recommencez.',
            default => null,
        };
    }

    private function validateUrl(string $input, ConversationState $state): ?string
    {
        if (mb_strlen($input) > 2048) {
            return 'Ce lien est trop long. Copiez le lien du produit depuis la barre d\'adresse.';
        }

        if (filter_var($input, FILTER_VALIDATE_URL) === false) {
            return 'Ce lien ne semble pas valide. Collez l\'adresse complète du produit, en commençant par https://';
        }

        if (! in_array(strtolower((string) parse_url($input, PHP_URL_SCHEME)), ['http', 'https'], strict: true)) {
            return 'Seuls les liens http et https sont acceptés.';
        }

        $marketplace = isset($state->draft['marketplace'])
            ? Marketplace::tryFrom($state->draft['marketplace'])
            : null;

        // A mismatch is only a hint: shortened and localised links are common,
        // and the administrator opens the link manually anyway.
        if ($marketplace !== null && ! $marketplace->matchesUrl($input)) {
            return sprintf(
                'Ce lien ne semble pas venir de %s. Vérifiez-le et renvoyez-le, ou recommencez pour changer de plateforme.',
                $marketplace->label(),
            );
        }

        return null;
    }

    private function validateQuantity(string $input): ?string
    {
        if (! ctype_digit($input)) {
            return 'Indiquez une quantité en chiffres, par exemple 1.';
        }

        $max = (int) config('shoprelle.requests.max_quantity_per_item');
        $quantity = (int) $input;

        if ($quantity < 1 || $quantity > $max) {
            return sprintf('La quantité doit être comprise entre 1 et %d.', $max);
        }

        return null;
    }

    private function validatePrice(string $input): ?string
    {
        $normalized = str_replace([' ', ','], ['', '.'], $input);

        if (! is_numeric($normalized)) {
            return 'Indiquez un prix en chiffres, par exemple 29.99. Vous pouvez aussi passer cette étape.';
        }

        $price = (float) $normalized;

        if ($price < 0 || $price > 1_000_000) {
            return 'Ce prix ne semble pas réaliste, merci de le vérifier.';
        }

        return null;
    }

    private function validatePhone(string $input): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $input) ?? '';

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return 'Ce numéro ne me semble pas complet. Indiquez-le avec l\'indicatif, par exemple +237 6 12 34 56 78.';
        }

        return null;
    }

    private function validateFullName(string $input): ?string
    {
        if (mb_strlen($input) > 120) {
            return 'Ce nom est un peu long — pouvez-vous le raccourcir ?';
        }

        if (count(preg_split('/\s+/', $input, -1, PREG_SPLIT_NO_EMPTY) ?: []) < 2) {
            return 'Il me faut votre prénom et votre nom, pour que le colis vous trouve.';
        }

        return null;
    }

    private function validateReference(string $input): ?string
    {
        return preg_match('/^[A-Za-z]{2,6}-[A-Za-z0-9]{2,8}-[A-Za-z0-9]{4,10}$/', $input) === 1
            ? null
            : 'Cette référence ne semble pas valide. Elle ressemble à SHP-2607-4KJ9X2.';
    }

    /**
     * Check the shape of an access code, not the code itself.
     *
     * Only the format is judged here; whether it opens anything is settled
     * against the stored hash by {@see ConversationManager}, which is also what
     * counts the attempt. Separators are tolerated because the code is shown
     * grouped and people type back what they see.
     */
    private function validateAccessCode(string $input): ?string
    {
        return preg_match('/^[A-Za-z0-9]{3}[ -]?[A-Za-z0-9]{3}$/', trim($input)) === 1
            ? null
            : 'Ce code ne semble pas valide. Il ressemble à K4M-9PZ et vous a été donné à la fin de votre première demande.';
    }

    private function validateRating(string $input): ?string
    {
        $rating = filter_var($input, FILTER_VALIDATE_INT);

        return $rating !== false && $rating >= Review::MIN_RATING && $rating <= Review::MAX_RATING
            ? null
            : 'Choisissez une note dans la liste.';
    }

    /**
     * The rating scale, drawn as stars so the meaning survives a channel that
     * shows nothing but the label.
     *
     * @return list<array{value: string, label: string}>
     */
    private function ratingOptions(): array
    {
        $wording = [
            5 => 'Excellent',
            4 => 'Bien',
            3 => 'Correct',
            2 => 'Décevant',
            1 => 'Mauvais',
        ];

        $options = [];

        foreach ($wording as $rating => $label) {
            $options[] = [
                'value' => (string) $rating,
                'label' => sprintf('%s %s', str_repeat('⭐', $rating), $label),
            ];
        }

        return $options;
    }

    /**
     * Store a validated answer on the draft item or on the customer details.
     */
    private function apply(ConversationState $state, Step $step, string $input): void
    {
        match ($step) {
            Step::Menu => $this->applyMenu($state, $input),
            Step::HelpTopic => $this->applyHelpTopic($state, $input),
            Step::Marketplace => $state->draft = ['marketplace' => $input, 'attachments' => []],
            Step::ProductUrl => $state->draft['product_url'] = $input,
            Step::Color => $state->draft['color'] = $input,
            Step::Size => $state->draft['size'] = $input,
            Step::Quantity => $state->draft['quantity'] = (int) $input,
            Step::DeclaredPrice => $this->applyPrice($state, $input),
            Step::ItemComment => $state->draft['comment'] = $input,
            Step::MoreItems => $state->wantsMoreItems = $input === self::YES,
            Step::Country => $state->customer['country'] = $input,
            Step::City => $state->customer['city'] = $input,
            Step::Phone => $state->customer['phone'] = $this->normalizePhone($input),
            Step::FullName => $this->applyFullName($state, $input),
            Step::Email => $state->customer['email'] = $input,
            Step::TrackReference => $state->tracking['reference'] = strtoupper($input),
            // Held until the access code arrives, which is what actually opens
            // the list. On its own it unlocks nothing.
            Step::MyOrdersPhone => $state->tracking['phone'] = $this->normalizePhone($input),
            Step::ReviewRating => $state->tracking['rating'] = (int) $input,
            Step::ContactMessage => $state->tracking['message'] = trim($input),
            Step::Screenshot, Step::Summary, Step::Completed,
            Step::TrackPhone, Step::MyOrdersCode, Step::ReviewComment, Step::ContactReply => null,
        };
    }

    /**
     * Answer a help subject, or leave the help menu.
     *
     * Clearing the intent is what next() reads to send the customer back to the
     * welcome menu, so the two stay in one place instead of a separate flag.
     */
    private function applyHelpTopic(ConversationState $state, string $input): void
    {
        $topic = HelpTopic::tryFrom($input);

        if ($topic === null) {
            $state->intent = null;

            return;
        }

        $state->pushBotMessage($topic->answer());
    }

    private function applyPrice(ConversationState $state, string $input): void
    {
        $normalized = str_replace([' ', ','], ['', '.'], $input);

        $state->draft['declared_price'] = number_format((float) $normalized, 2, '.', '');
        $state->draft['declared_currency'] = config('shoprelle.declared_currency');
    }

    private function applyFullName(ConversationState $state, string $input): void
    {
        $parts = preg_split('/\s+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $state->customer['first_name'] = array_shift($parts) ?? '';
        $state->customer['last_name'] = implode(' ', $parts);
    }

    /**
     * Keep digits and a single leading plus, so the same person is recognised
     * whatever spacing they typed.
     */
    public function normalizePhone(string $input): string
    {
        $digits = preg_replace('/[^0-9]/', '', $input) ?? '';

        return str_starts_with(trim($input), '+') ? '+'.$digits : $digits;
    }
}
