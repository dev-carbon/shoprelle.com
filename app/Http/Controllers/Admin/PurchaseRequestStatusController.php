<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestStatusService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PurchaseRequestStatusController extends Controller
{
    public function __construct(
        private PurchaseRequestStatusService $statuses,
    ) {}

    /**
     * Move a request to another status, recording the change.
     */
    public function update(UpdateStatusRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        try {
            $this->statuses->transition(
                $purchaseRequest,
                $request->status(),
                $request->user(),
                $request->comment(),
            );
        } catch (InvalidStatusTransition $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Statut mis à jour.']);

        return back();
    }
}
