<?php

namespace App\Http\Controllers;

use App\Chatbot\Channel;
use App\Chatbot\ConversationManager;
use App\Enums\Marketplace;
use App\Exceptions\ConversationException;
use App\Http\Requests\Chatbot\SendMessageRequest;
use App\Http\Requests\Chatbot\StartFromLinkRequest;
use App\Http\Requests\Chatbot\UploadAttachmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public, unauthenticated purchase request assistant.
 *
 * Every action replies with a redirect back to the conversation, so the page
 * always renders the state the server holds: a reload or a lost connection can
 * never desynchronise the flow.
 */
class ChatbotController extends Controller
{
    public function __construct(
        private ConversationManager $conversations,
    ) {}

    /**
     * Show the assistant with the conversation in progress, if any.
     */
    public function show(Request $request): Response
    {
        $state = $this->conversations->current($this->conversationKey($request));

        return Inertia::render('chat', [
            'conversation' => $this->conversations->present($state),
            'marketplaces' => Marketplace::options(),
        ]);
    }

    /**
     * Answer the current question.
     */
    public function message(SendMessageRequest $request): RedirectResponse
    {
        $key = $this->conversationKey($request);

        if (! $this->allowLookup($request, $key)) {
            return back()->withErrors([
                'message' => 'Trop de recherches successives. Merci de réessayer dans une minute.',
            ]);
        }

        $this->conversations->reply($key, $request->message());

        return back();
    }

    /**
     * Open a request from a link pasted on the landing page.
     *
     * Redirects to the assistant rather than back: the visitor is arriving at
     * the conversation, not answering inside it.
     */
    public function link(StartFromLinkRequest $request): RedirectResponse
    {
        $this->conversations->startFromLink(
            $this->conversationKey($request),
            $request->url(),
        );

        return to_route('chat.show');
    }

    /**
     * Ouvrir une demande depuis une marque cliquée sur la vitrine.
     *
     * La tuile répond à la première question du parcours — la plateforme — et
     * l'assistant enchaîne sur la suivante : le lien du produit.
     */
    public function start(Request $request, Marketplace $marketplace): RedirectResponse
    {
        $this->conversations->startFromMarketplace(
            $this->conversationKey($request),
            $marketplace,
        );

        return to_route('chat.show');
    }

    /**
     * Ouvrir l'assistant sur « nous écrire », au sujet d'une demande.
     *
     * La référence n'ouvre aucun accès : elle n'est qu'un sujet de message. Ce
     * qui donne accès à une demande reste le numéro et le code, sur « Mes
     * demandes ».
     */
    public function contact(Request $request, ?string $reference = null): RedirectResponse
    {
        $this->conversations->startContact($this->conversationKey($request), $reference);

        return to_route('chat.show');
    }

    /**
     * Return to the welcome menu.
     */
    public function menu(Request $request): RedirectResponse
    {
        $this->conversations->backToMenu($this->conversationKey($request));

        return back();
    }

    /**
     * Move past an optional question.
     */
    public function skip(Request $request): RedirectResponse
    {
        $this->conversations->skip($this->conversationKey($request));

        return back();
    }

    /**
     * Attach a screenshot to the product being described.
     */
    public function upload(UploadAttachmentRequest $request): RedirectResponse
    {
        try {
            $this->conversations->upload($this->conversationKey($request), $request->screenshot());
        } catch (ConversationException $exception) {
            return back()->withErrors(['screenshot' => $exception->getMessage()]);
        }

        return back();
    }

    /**
     * Confirm the recap and hand the request over to the back office.
     */
    public function confirm(Request $request): RedirectResponse
    {
        try {
            $this->conversations->confirm($this->conversationKey($request));
        } catch (ConversationException $exception) {
            return back()->withErrors(['conversation' => $exception->getMessage()]);
        }

        return back();
    }

    /**
     * Discard the conversation and start over.
     */
    public function restart(Request $request): RedirectResponse
    {
        $this->conversations->restart($this->conversationKey($request));

        return back();
    }

    /**
     * The key identifying who we are talking to.
     *
     * On the web a constant is correct: the conversation lives in the visitor's
     * own session, which already isolates them. It must not be the session id,
     * which Laravel regenerates (on login, or on token refresh) and would drop
     * a conversation mid-flow. A Telegram or WhatsApp entry point keys by chat
     * id or phone number instead, against the cache-backed store.
     */
    private function conversationKey(Request $request): string
    {
        return Channel::Web->key();
    }

    /**
     * Tracking steps are answered with a phone number, so they are throttled
     * far more tightly than ordinary answers: without this, the endpoint would
     * let someone enumerate numbers to discover who has orders.
     */
    private function allowLookup(Request $request, string $key): bool
    {
        if (! $this->conversations->current($key)->step->needsLookup()) {
            return true;
        }

        return RateLimiter::attempt(
            'chat-lookup:'.$request->ip(),
            maxAttempts: 5,
            callback: fn (): bool => true,
            decaySeconds: 60,
        ) !== false;
    }
}
