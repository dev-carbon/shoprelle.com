<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendQuoteRequest;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestStatusService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function __construct(
        private PurchaseRequestStatusService $statuses,
    ) {}

    /**
     * Record the quote and mark the request as quoted.
     */
    public function store(SendQuoteRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        try {
            $this->statuses->sendQuote($purchaseRequest, $request->toQuoteData(), $request->user());
        } catch (InvalidStatusTransition $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Devis enregistré et demande mise à jour.']);

        return back();
    }
}
