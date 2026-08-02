<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\PurchaseRequestFilters;
use App\Enums\Marketplace;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestDetailResource;
use App\Http\Resources\PurchaseRequestListResource;
use App\Models\PurchaseRequest;
use App\Repositories\Contracts\PurchaseRequestRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequestController extends Controller
{
    public function __construct(
        private PurchaseRequestRepository $requests,
    ) {}

    /**
     * List every purchase request, filtered and paginated.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $filters = PurchaseRequestFilters::fromRequest($request);
        $paginator = $this->requests->paginateForAdmin($filters);

        return Inertia::render('admin/requests/index', [
            'requests' => PurchaseRequestListResource::collection($paginator),
            'filters' => $filters->toArray(),
            'statuses' => PurchaseRequestStatus::options(),
            'marketplaces' => Marketplace::options(),
            'countries' => config('shoprelle.countries'),
        ]);
    }

    /**
     * Show one request with its items, history, notes and attachments.
     */
    public function show(Request $request, PurchaseRequest $purchaseRequest): Response
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest = $this->requests->loadForAdmin($purchaseRequest);

        return Inertia::render('admin/requests/show', [
            'request' => new PurchaseRequestDetailResource($purchaseRequest),
            'availableTransitions' => array_map(
                fn (PurchaseRequestStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                $purchaseRequest->status->allowedTransitions(),
            ),
            'quoteCurrency' => config('shoprelle.quote_currency'),
            'costCurrency' => config('shoprelle.declared_currency'),
            'paymentMethods' => PaymentMethod::options(),
            'canRecordPayment' => $request->user()->can('recordPayment', $purchaseRequest),
        ]);
    }
}
