<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestListResource;
use App\Models\PurchaseRequest;
use App\Repositories\Contracts\PurchaseRequestRepository;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private PurchaseRequestRepository $requests,
    ) {}

    /**
     * Back-office overview: volumes per status and the latest requests.
     */
    public function __invoke(): Response
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        return Inertia::render('dashboard', [
            'statistics' => $this->requests->statistics(),
            'statuses' => PurchaseRequestStatus::options(),
            'latestRequests' => PurchaseRequestListResource::collection(
                PurchaseRequest::query()
                    ->with(['customer', 'items'])
                    ->withCount('items')
                    ->latest()
                    ->limit(8)
                    ->get(),
            ),
        ]);
    }
}
