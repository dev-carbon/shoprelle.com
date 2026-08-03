<?php

namespace App\Chatbot;

use App\Chatbot\Contracts\ConversationStore;
use App\DataTransferObjects\ReviewData;
use App\Exceptions\ConversationException;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepository;
use App\Repositories\Contracts\PurchaseRequestRepository;
use App\Services\AttachmentService;
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
     * How many access codes may be tried against one phone number, and over
     * what window. The chatbot route itself allows sixty requests a minute,
     * which is far too generous for guessing a credential.
     */
    private const CODE_ATTEMPTS = 5;

    private const CODE_DECAY_SECONDS = 3600;

    /**
     * How many reviews one conversation may leave, and over what window. The
     * assistant is open to anybody, so the only thing standing between it and a
     * flood of ratings is this.
     */
    private const REVIEW_ATTEMPTS = 3;

    private const REVIEW_DECAY_SECONDS = 3600;

    public function __construct(
        private ConversationStore $store,
        private ChatbotEngine $engine,
        private PurchaseRequestService $requests,
        private PurchaseRequestRepository $repository,
        private AttachmentService $attachments,
        private CustomerRepository $customers,
        private ReviewService $reviews,
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

        return $this->persist($key, $this->engine->skip($state));
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
     * List a customer's requests, but only once the access code checks out.
     *
     * Attempts are counted per phone number rather than per session or IP: the
     * session is thrown away by clearing a cookie, and the number is the thing
     * actually being attacked. Five tries an hour turns a six-character code
     * into something no amount of patience gets through.
     */
    private function resolveMyOrders(ConversationState $state, string $input): ConversationState
    {
        $phone = (string) ($state->tracking['phone'] ?? '');

        $limiter = 'my-orders:'.sha1($phone);

        if (RateLimiter::tooManyAttempts($limiter, self::CODE_ATTEMPTS)) {
            return $this->engine->reject($state, $input, sprintf(
                'Trop de tentatives. Réessayez dans %d minutes, ou écrivez-nous à %s.',
                (int) ceil(RateLimiter::availableIn($limiter) / 60),
                config('shoprelle.contact.email'),
            ));
        }

        $customer = $this->customers->findByPhone($phone);

        if ($customer === null || ! $customer->matchesAccessCode($input)) {
            RateLimiter::hit($limiter, self::CODE_DECAY_SECONDS);

            return $this->engine->presentOrders($state, $input, []);
        }

        RateLimiter::clear($limiter);

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
