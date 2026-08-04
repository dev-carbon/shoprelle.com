<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ce que les visiteurs nous ont écrit depuis l'assistant.
 *
 * Les messages en attente d'abord, et le plus ancien en tête : une file
 * d'attente se traite dans l'ordre où elle s'est formée, pas dans l'ordre
 * inverse. Les messages déjà traités restent dessous, sans pagination tant que
 * le volume ne l'exige pas.
 */
class ContactMessageController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ContactMessage::class);

        $messages = ContactMessage::query()
            ->with(['customer', 'handler'])
            ->orderByRaw('handled_at is not null')
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn (ContactMessage $message): array => [
                'id' => $message->id,
                'message' => $message->message,
                'reply_to' => $message->reply_to,
                'channel' => $message->channel->value,
                'customer_name' => $message->customer?->first_name,
                'customer_id' => $message->customer_id,
                'handled_at' => $message->handled_at?->toIso8601String(),
                'handled_by' => $message->handler?->name,
                'created_at' => $message->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/messages/index', [
            'messages' => $messages,
            'pending' => ContactMessage::query()->pending()->count(),
        ]);
    }

    /**
     * Marquer un message comme traité, ou le rouvrir.
     *
     * Le même geste dans les deux sens : se tromper de bouton doit se réparer
     * par le même bouton.
     */
    public function update(ContactMessage $contactMessage): RedirectResponse
    {
        $this->authorize('handle', $contactMessage);

        $contactMessage->update($contactMessage->handled_at === null
            ? ['handled_at' => now(), 'handled_by' => request()->user()?->id]
            : ['handled_at' => null, 'handled_by' => null]
        );

        return back();
    }
}
