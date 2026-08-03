<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerRequestResource;
use App\Models\PurchaseRequest;
use App\Repositories\Contracts\PurchaseRequestRepository;
use App\Services\CustomerAccessService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mes demandes": what a customer can read about their own requests.
 *
 * Every lookup starts from the identified customer and is scoped to them, so a
 * reference belonging to somebody else is not found rather than found and
 * refused — there is nothing to learn from the difference.
 */
class CustomerRequestController extends Controller
{
    public function __construct(
        private CustomerAccessService $access,
        private PurchaseRequestRepository $requests,
    ) {}

    /**
     * The customer's requests, or the form that opens them.
     *
     * One address for both: it is what a customer bookmarks, and it must keep
     * working once the session behind it has expired.
     */
    public function index(): Response
    {
        $customer = $this->access->identified();

        if ($customer === null) {
            return Inertia::render('orders/access');
        }

        return Inertia::render('orders/index', [
            'customer' => [
                'first_name' => $customer->first_name,
                'phone' => $customer->phone,
            ],
            'requests' => $this->requests->listForCustomer($customer)->map(
                fn (PurchaseRequest $request): array => [
                    'reference' => $request->reference,
                    'status_label' => $request->status->label(),
                    'status_color' => $request->status->color(),
                    'item_count' => (int) ($request->items_count ?? 0),
                    'created_at' => $request->created_at?->toIso8601String(),
                    'total_amount' => $request->quote_total_amount,
                    'currency' => $request->quote_currency,
                ],
            )->all(),
        ]);
    }

    public function show(string $reference): Response|RedirectResponse
    {
        $customer = $this->access->identified();

        if ($customer === null) {
            return to_route('orders.index');
        }

        $request = $this->requests->findForCustomer($customer, $reference);

        abort_if($request === null, 404);

        return Inertia::render('orders/show', [
            'request' => new CustomerRequestResource($request),
        ]);
    }
}
