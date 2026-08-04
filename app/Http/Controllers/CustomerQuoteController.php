<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidStatusTransition;
use App\Models\PurchaseRequest;
use App\Repositories\Contracts\PurchaseRequestRepository;
use App\Services\CustomerAccessService;
use App\Services\PurchaseRequestStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Le client répond à son devis.
 *
 * La vitrine promet « nous n'achetons qu'après votre validation du devis — et
 * vous pouvez dire non ». Ces deux gestes sont cette promesse ; sans eux elle
 * n'était tenue que par téléphone.
 */
class CustomerQuoteController extends Controller
{
    public function __construct(
        private CustomerAccessService $access,
        private PurchaseRequestRepository $requests,
        private PurchaseRequestStatusService $statuses,
    ) {}

    public function accept(string $reference): RedirectResponse
    {
        return $this->decide($reference, function (PurchaseRequest $request): void {
            $this->statuses->acceptQuote($request);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Devis accepté. Voici comment le régler.',
            ]);
        });
    }

    public function decline(Request $request, string $reference): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->decide($reference, function (PurchaseRequest $purchaseRequest) use ($validated): void {
            $this->statuses->declineQuote($purchaseRequest, $validated['reason'] ?? null);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Devis refusé. Nous revenons vers vous avec une nouvelle proposition.',
            ]);
        });
    }

    /**
     * Le tronc commun des deux réponses : qui parle, de quelle demande, et que
     * faire quand le cycle de vie refuse le changement.
     *
     * Un devis déjà accepté, déjà payé ou déjà refusé arrive ici dès que deux
     * onglets sont restés ouverts. Ce n'est pas une faute du client : la page
     * se recharge sur l'état réel et lui dit ce qu'il en est.
     */
    private function decide(string $reference, callable $decision): RedirectResponse
    {
        $customer = $this->access->identified();

        if ($customer === null) {
            return to_route('orders.index');
        }

        $request = $this->requests->findForCustomer($customer, $reference);

        abort_if($request === null, 404);

        try {
            $decision($request);
        } catch (InvalidStatusTransition) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Cette demande a changé entre-temps. Voici où elle en est.',
            ]);
        }

        return to_route('orders.show', $request->reference);
    }
}
