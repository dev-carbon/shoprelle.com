<?php

namespace App\Repositories\Eloquent;

use App\DataTransferObjects\PurchaseRequestFilters;
use App\Enums\PurchaseRequestStatus;
use App\Models\Customer;
use App\Models\PurchaseRequest;
use App\Repositories\Contracts\PurchaseRequestRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentPurchaseRequestRepository implements PurchaseRequestRepository
{
    public function findByReference(string $reference): ?PurchaseRequest
    {
        return PurchaseRequest::query()->where('reference', $reference)->first();
    }

    public function findForTracking(string $reference, string $phone): ?PurchaseRequest
    {
        return PurchaseRequest::query()
            ->withCount('items')
            ->where('reference', $reference)
            ->whereHas('customer', fn (Builder $query) => $query->where('phone', $phone))
            ->first();
    }

    public function listForPhone(string $phone, int $limit = 10): Collection
    {
        return PurchaseRequest::query()
            ->whereHas('customer', fn (Builder $query) => $query->where('phone', $phone))
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function listForCustomer(Customer $customer): Collection
    {
        return $customer->purchaseRequests()
            ->withCount('items')
            ->latest()
            ->get();
    }

    public function findForCustomer(Customer $customer, string $reference): ?PurchaseRequest
    {
        return $customer->purchaseRequests()
            /*
             * L'historique vient avec : c'est ce que le client suit, et sur le
             * web c'est même tout ce qu'il a — un client sans adresse email ne
             * reçoit aucune notification, il revient regarder sa page.
             */
            ->with([
                'items.attachments',
                'payments',
                'statusHistories' => fn ($query) => $query->oldest()->orderBy('id'),
            ])
            ->where('reference', $reference)
            ->first();
    }

    public function paginateForAdmin(PurchaseRequestFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return PurchaseRequest::query()
            ->with(['customer', 'items'])
            ->withCount('items')
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->orderBy($filters->sort, $filters->direction)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadForAdmin(PurchaseRequest $request): PurchaseRequest
    {
        return $request->load([
            'customer',
            'items.attachments',
            'attachments',
            'statusHistories.user',
            'adminNotes.user',
            'payments.recordedBy',
        ]);
    }

    public function statistics(): array
    {
        /** @var array<string, int> $counts */
        $counts = PurchaseRequest::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $byStatus = [];
        $active = 0;

        foreach (PurchaseRequestStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            $byStatus[$status->value] = $count;

            if (! $status->isFinal()) {
                $active += $count;
            }
        }

        return [
            'total' => array_sum($byStatus),
            'active' => $active,
            'by_status' => $byStatus,
            'last_seven_days' => PurchaseRequest::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }

    /**
     * @param  Builder<PurchaseRequest>  $query
     */
    private function applyFilters(Builder $query, PurchaseRequestFilters $filters): void
    {
        $query
            ->search($filters->search)
            ->when(
                $filters->status,
                fn (Builder $query, PurchaseRequestStatus $status) => $query->where('status', $status),
            )
            ->when(
                $filters->marketplace,
                fn (Builder $query, $marketplace) => $query->whereHas(
                    'items',
                    fn (Builder $items) => $items->where('marketplace', $marketplace),
                ),
            )
            ->when(
                $filters->country,
                fn (Builder $query, string $country) => $query->where('country', $country),
            )
            ->when(
                $filters->from,
                fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from),
            )
            ->when(
                $filters->to,
                fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to),
            );
    }
}
