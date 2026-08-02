<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminNoteRequest;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdminNoteController extends Controller
{
    /**
     * Attach an internal note to a request.
     */
    public function store(StoreAdminNoteRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $purchaseRequest->adminNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $request->string('body')->trim()->value(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Note ajoutée.']);

        return back();
    }
}
