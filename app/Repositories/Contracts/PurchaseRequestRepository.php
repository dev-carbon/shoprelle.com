<?php

namespace App\Repositories\Contracts;

use App\DataTransferObjects\PurchaseRequestFilters;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseRequestRepository
{
    public function findByReference(string $reference): ?PurchaseRequest;

    /**
     * Look up a request for tracking.
     *
     * The reference alone is not enough: it is paired with the phone number the
     * request was created with, so a leaked reference does not expose an order.
     */
    public function findForTracking(string $reference, string $phone): ?PurchaseRequest;

    /**
     * The most recent requests placed from a phone number.
     *
     * @return Collection<int, PurchaseRequest>
     */
    public function listForPhone(string $phone, int $limit = 10): Collection;

    /**
     * Paginate requests for the back office, eager loading everything the list
     * renders so the table never triggers per-row queries.
     *
     * @return LengthAwarePaginator<int, PurchaseRequest>
     */
    public function paginateForAdmin(PurchaseRequestFilters $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Load a request with every relation the detail screen needs.
     */
    public function loadForAdmin(PurchaseRequest $request): PurchaseRequest;

    /**
     * Counts per status, plus the totals shown on the dashboard.
     *
     * @return array{total: int, active: int, by_status: array<string, int>, last_seven_days: int}
     */
    public function statistics(): array;
}
