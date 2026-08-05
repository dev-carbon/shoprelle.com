<?php

namespace App\Chatbot;

use App\Chatbot\Contracts\ConversationStore;
use App\DataTransferObjects\ReviewData;
use App\Enums\Marketplace;
use App\Exceptions\ConversationException;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Repositories\Contracts\PurchaseRequestRepository;
use App\Services\AttachmentService;
use App\Services\CustomerAccessService;
use App\Services\PurchaseRequestService;
use App\Services\ReviewService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Drives a conversation end to end: loads the state, hands it to the engine,
 * persists the result, and resolves the steps the engine cannot answer alone.
 *
 * The engine stays free of infrastructure; everything that touches storage or
 * the database happens here. A Telegram or WhatsApp entry point talks to this
 * class exactly as the web controller does, only with a different key.
 */
class ConversationManager
{
    /**
     * How many reviews one conversation may leave, and over what window. The
     * assistant is open to anybody, so the only thing standing between it and a
     * flood of ratings is this.
     */
    private const REVIEW_ATTEMPTS = 3;

    private const REVIEW_DECAY_SECONDS = 3600;

    /**
     * Combien de messages une même conversation peut nous adresser, et sur
     * quelle durée. Plus large que pour les avis — écrire deux fois parce qu'on
     * a oublié quelque chose est normal — mais borné : cette table est ouverte
     * au public, sans compte ni captcha.
     */
    private const CONTACT_ATTEMPTS = 5;

    private const CONTACT_DECAY_SECONDS = 3600;

    public function __construct(
        private ConversationStore $store,
        private ChatbotEngine $engine,
        private PurchaseRequestService $requests,
        private PurchaseRequestRepository $repository,
        private AttachmentService $attachments,
        private ReviewService $reviews,
        private CustomerAccessService $access,
    ) {}

    /**
     * The current conversation for this key, started on first contact.
     *
     * The channel is read off the key, so no caller has to pass it twice.
     */
    public function current(string $key): ConversationState
    {
        $state = $this->store->find($key);

        if ($state === null) {
            $state = $this->engine->start(Channel::of($key));
            $this->store->save($key, $state);
        }

        return $state;
    }

    /**
     * Answer the current question.
     */
    public function reply(string $key, string $input): ConversationState
    {
        $state = $this->current($key);

        // Confirmation and restart have their own entry points so they can carry
        // stricter rate limits; treat them as such wherever they arrive from.
        if ($this->engine->isConfirmation($state, $input)) {
            return $this->confirm($key);
        }

        if ($state->step === Step::Summary && trim($input) === ChatbotEngine::RESTART) {
            return $this->restart($key);
        }

        if ($state->step->needsLookup()) {
            return $this->persist($key, $this->resolveLookup($key, $state, $input));
        }

        return $this->persist($key, $this->engine->handle($state, $input));
    }

    /**
     * Start a fresh request from a link pasted outside the conversation.
     *
     * Whatever was in progress is discarded first: the visitor asked for a new
     * request by pasting one, and silently grafting a link onto a half-finished
     * conversation is the surprising reading.
     */
    public function startFromLink(string $key, string $url): ConversationState
    {
        $state = $this->restart($key);

        return $this->persist($key, $this->engine->startFromLink($state, $url));
    }

    /**
     * Start a fresh request from a marketplace chosen outside the conversation.
     *
     * Same rule as a pasted link: clicking a brand asks for a new request, so
     * whatever was in progress is discarded rather than grafted onto.
     */
    public function startFromMarketplace(string $key, Marketplace $marketplace): ConversationState
    {
        $state = $this->restart($key);

        return $this->persist($key, $this->engine->startFromMarketplace($state, $marketplace));
    }

    /**
     * Move past an optional step.
     */
    public function skip(string $key): ConversationState
    {
        $state = $this->current($key);

        // A review left without a word is still a review. The engine's skip()
        // only advances the step, which here would drop the rating on the floor,
        // so this one step is finished the same way an answer would finish it.
        if ($state->step === Step::ReviewComment) {
            return $this->persist($key, $this->resolveReview($key, $state, ''));
        }

        // Même chose pour un message laissé sans moyen de rappel : le message
        // est écrit, il ne manque que l'adresse à laquelle répondre. Passer
        // sans écrire perdrait ce que la personne est venue dire.
        if ($state->step === Step::ContactReply) {
            return $this->persist($key, $this->resolveContactMessage($key, $state, ''));
        }

        return $this->persist($key, $this->engine->skip($state));
    }

    /**
     * Ouvrir la conversation sur « nous écrire », depuis une page qui sait déjà
     * de quelle demande il s'agit.
     */
    public function startContact(string $key, ?string $reference = null): ConversationState
    {
        $state = $this->current($key);

        return $this->persist($key, $this->engine->startContact($state, $reference));
    }

    /**
     * Go back to the welcome menu, abandoning the request being described.
     */
    public function backToMenu(string $key): ConversationState
    {
        $state = $this->current($key);

        if ($state->isCompleted()) {
            return $this->restart($key);
        }

        return $this->persist($key, $this->engine->returnToMenu($state));
    }

    /**
     * Store a screenshot for the product being described.
     *
     * @throws ConversationException
     */
    public function upload(string $key, UploadedFile $file): ConversationState
    {
        $state = $this->expectScreenshotStep($key);

        $pending = $this->attachments->storePending($file, $state->id);

        return $this->persist($key, $this->engine->attach($state, $pending));
    }

    /**
     * Store a screenshot received as raw bytes, the way messaging channels
     * deliver photos.
     *
     * @throws ConversationException
     */
    public function uploadContents(string $key, string $contents, string $originalName): ConversationState
    {
        $state = $this->expectScreenshotStep($key);

        $pending = $this->attachments->storePendingContents($contents, $originalName, $state->id);

        return $this->persist($key, $this->engine->attach($state, $pending));
    }

    /**
     * Persist the conversation as a purchase request and close it.
     *
     * @throws ConversationException when the conversation is not ready
     */
    public function confirm(string $key): ConversationState
    {
        $state = $this->current($key);

        if ($state->step !== Step::Summary) {
            throw new ConversationException('La demande n\'est pas encore complète.');
        }

        $request = $this->requests->submit(
            $state->toPurchaseRequestData(Channel::identifierOf($key)),
        );

        $accessCode = $request->customer->plainAccessCode;

        return $this->persist($key, $this->engine->complete(
            $state,
            $request->reference,
            $accessCode === null ? null : Customer::formatAccessCode($accessCode),
        ));
    }

    /**
     * Abandon the conversation and start a new one, discarding staged uploads.
     */
    public function restart(string $key): ConversationState
    {
        $state = $this->store->find($key);

        if ($state !== null && ! $state->isCompleted()) {
            $this->attachments->discardPending($state->id);
        }

        $this->store->forget($key);

        return $this->current($key);
    }

    /**
     * The payload a channel renders.
     *
     * @return array<string, mixed>
     */
    public function present(ConversationState $state): array
    {
        return [
            'id' => $state->id,
            'messages' => array_map(
                fn (ChatMessage $message): array => $message->toArray(),
                $state->messages,
            ),
            'current' => $this->engine->describe($state),
            'summary' => $state->step === Step::Summary ? $this->engine->summarize($state) : null,
            'intent' => $state->intent?->value,
            'item_count' => count($state->items),
            'attachment_count' => count($state->draft['attachments'] ?? []),
            'reference' => $state->reference,
            'completed' => $state->isCompleted(),
        ];
    }

    /**
     * Answer a step whose reply depends on stored data.
     */
    private function resolveLookup(string $key, ConversationState $state, string $input): ConversationState
    {
        $error = $this->engine->validateAnswer($state, $input);

        if ($error !== null) {
            return $this->engine->reject($state, $input, $error);
        }

        return match ($state->step) {
            Step::TrackPhone => $this->engine->presentTracking(
                $state,
                $input,
                $this->trackingPayload(
                    $state->tracking['reference'] ?? '',
                    $this->engine->normalizePhone($input),
                ),
            ),
            Step::MyOrdersCode => $this->resolveMyOrders($state, $input),
            Step::ReviewComment => $this->resolveReview($key, $state, $input),
            Step::ContactReply => $this->resolveContactMessage($key, $state, $input),
            default => $state,
        };
    }

    /**
     * Store the review the conversation has just finished gathering.
     *
     * The rating has to be read before presentReview(), which returns to the
     * menu and clears `tracking` on the way — the thanks come after the write,
     * not instead of it.
     */
    private function resolveReview(string $key, ConversationState $state, string $input): ConversationState
    {
        $limiter = 'reviews:'.sha1($key);

        if (RateLimiter::tooManyAttempts($limiter, self::REVIEW_ATTEMPTS)) {
            return $this->engine->reject($state, $input, sprintf(
                'Vous avez déjà laissé plusieurs avis. Réessayez dans %d minutes, ou écrivez-nous à %s.',
                (int) ceil(RateLimiter::availableIn($limiter) / 60),
                config('shoprelle.contact.email'),
            ));
        }

        RateLimiter::hit($limiter, self::REVIEW_DECAY_SECONDS);

        $comment = trim($input);

        $this->reviews->record(new ReviewData(
            rating: (int) ($state->tracking['rating'] ?? 0),
            channel: $state->channel,
            comment: $comment === '' ? null : $comment,
            phone: $state->knownPhone,
            reference: $state->reference,
        ));

        return $this->engine->presentReview($state, $input);
    }

    /**
     * Écrire le message que la conversation vient de recueillir.
     *
     * Le texte a été retenu à l'étape d'avant ; ce qui arrive ici est le moyen
     * de rappel, facultatif. L'écriture précède le remerciement — celui-ci
     * revient au menu et vide `tracking` au passage.
     *
     * Le client est rattaché quand la conversation a prouvé un numéro : c'est
     * ce qui permet au back-office de relier un message à un historique. Sans
     * numéro prouvé, le message existe quand même, sans propriétaire.
     */
    private function resolveContactMessage(string $key, ConversationState $state, string $input): ConversationState
    {
        $limiter = 'contact:'.sha1($key);

        if (RateLimiter::tooManyAttempts($limiter, self::CONTACT_ATTEMPTS)) {
            return $this->engine->reject($state, $input, sprintf(
                'Vous nous avez déjà écrit plusieurs fois. Réessayez dans %d minutes, ou écrivez-nous à %s.',
                (int) ceil(RateLimiter::availableIn($limiter) / 60),
                config('shoprelle.contact.email'),
            ));
        }

        RateLimiter::hit($limiter, self::CONTACT_DECAY_SECONDS);

        $message = trim((string) ($state->tracking['message'] ?? ''));
        $replyTo = trim($input);

        /*
         * La référence, quand la question vient de la page d'une demande. Elle
         * est écrite dans le message plutôt que dans une colonne à elle : c'est
         * ce que fait quelqu'un qui écrit un email, et le back-office la lit au
         * même endroit que le reste.
         */
        $reference = trim((string) ($state->tracking['reference'] ?? ''));

        if ($message !== '' && $reference !== '') {
            $message = sprintf('[Demande %s] %s', $reference, $message);
        }

        if ($message !== '') {
            ContactMessage::create([
                'customer_id' => $state->knownPhone === null
                    ? null
                    : Customer::query()->where('phone', $state->knownPhone)->value('id'),
                'message' => $message,
                'reply_to' => $replyTo === '' ? null : $replyTo,
                'channel' => $state->channel,
            ]);
        }

        return $this->engine->presentContactMessage($state, $input);
    }

    /**
     * List a customer's requests, but only once the access code checks out.
     *
     * The attempt budget belongs to {@see CustomerAccessService} and is shared
     * with the "mes demandes" page, which asks the very same question.
     */
    private function resolveMyOrders(ConversationState $state, string $input): ConversationState
    {
        $phone = (string) ($state->tracking['phone'] ?? '');

        if ($this->access->hasTooManyAttempts($phone)) {
            return $this->engine->reject($state, $input, sprintf(
                'Trop de tentatives. Réessayez dans %d minutes, ou écrivez-nous à %s.',
                $this->access->minutesUntilRetry($phone),
                config('shoprelle.contact.email'),
            ));
        }

        if ($this->access->attempt($phone, $input) === null) {
            return $this->engine->presentOrders($state, $input, []);
        }

        // The code checked out, so this conversation has proven the number is
        // its own. Anything asked later in the session can rely on it.
        $state->knownPhone = $phone;

        return $this->engine->presentOrders($state, $input, $this->ordersPayload($phone));
    }

    /**
     * @return array{reference: string, status_label: string, item_count: int, created_at: string, quote: string|null}|null
     */
    private function trackingPayload(string $reference, string $phone): ?array
    {
        $request = $this->repository->findForTracking($reference, $phone);

        if ($request === null) {
            return null;
        }

        return [
            'reference' => $request->reference,
            'status_label' => $request->status->label(),
            'item_count' => (int) ($request->items_count ?? 0),
            'created_at' => $request->created_at?->translatedFormat('d/m/Y') ?? '',
            'quote' => $request->isQuoted()
                ? $request->quote_total_amount.' '.$request->quote_currency
                : null,
        ];
    }

    /**
     * Only the reference, the status and the date are exposed here: this lookup
     * is protected by a phone number alone, so it must not reveal what was
     * ordered, from where, or for how much.
     *
     * @return list<array{reference: string, status_label: string, created_at: string}>
     */
    private function ordersPayload(string $phone): array
    {
        $orders = [];

        foreach ($this->repository->listForPhone($phone) as $request) {
            $orders[] = [
                'reference' => $request->reference,
                'status_label' => $request->status->label(),
                'created_at' => $request->created_at?->translatedFormat('d/m/Y') ?? '',
            ];
        }

        return $orders;
    }

    /**
     * @throws ConversationException when the conversation is not asking for one
     */
    private function expectScreenshotStep(string $key): ConversationState
    {
        $state = $this->current($key);

        if ($state->step !== Step::Screenshot) {
            throw new ConversationException('Aucune capture n\'est attendue à cette étape.');
        }

        return $state;
    }

    private function persist(string $key, ConversationState $state): ConversationState
    {
        $this->store->save($key, $state);

        return $state;
    }
}
