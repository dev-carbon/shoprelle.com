<?php

namespace App\Repositories\Eloquent;

use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\CustomerFilters;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentCustomerRepository implements CustomerRepository
{
    public function findByPhone(string $phone): ?Customer
    {
        return Customer::query()->where('phone', $phone)->first();
    }

    public function updateOrCreate(CustomerData $data): Customer
    {
        $attributes = $data->toArray();
        $phone = $attributes['phone'];
        unset($attributes['phone']);

        $customer = Customer::query()->updateOrCreate(['phone' => $phone], $attributes);

        // Issued here rather than at the call site so that every customer has a
        // code however they were created. A returning one keeps theirs, and the
        // save is a no-op when nothing changed.
        $customer->ensureAccessCode();
        $customer->save();

        return $customer;
    }

    public function paginateForAdmin(CustomerFilters $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::query()
            ->withCount('purchaseRequests')
            ->withMax('purchaseRequests', 'created_at')
            ->when($filters->search, $this->searchFilter(...))
            ->when(
                $filters->country,
                fn (Builder $query, string $country) => $query->where('country', $country),
            )
            ->orderBy($filters->sort, $filters->direction)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadForAdmin(Customer $customer): Customer
    {
        return $customer->load([
            // Ordered by id as well as date: two requests placed in the same
            // second must still list in a stable, newest-first order.
            'purchaseRequests' => fn ($query) => $query
                ->withCount('items')
                ->latest()
                ->latest('id'),
        ]);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    private function searchFilter(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('city', 'like', $like);
        });
    }
}
