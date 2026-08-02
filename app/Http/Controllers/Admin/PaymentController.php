<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Models\PurchaseRequest;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
    ) {}

    /**
     * Record money received against a request.
     */
    public function store(StorePaymentRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->payments->record($purchaseRequest, $request->toPaymentData(), $request->user());

        // The service reloads the payments on this very instance, so the
        // balance below already accounts for the row it just wrote.
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $purchaseRequest->isSettled()
                ? 'Paiement enregistré. La demande est intégralement réglée.'
                : 'Paiement enregistré. Il reste un solde à percevoir.',
        ]);

        return back();
    }
}
