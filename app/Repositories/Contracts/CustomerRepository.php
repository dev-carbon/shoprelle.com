<?php

namespace App\Repositories\Contracts;

use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\CustomerFilters;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerRepository
{
    public function findByPhone(string $phone): ?Customer;

    /**
     * Paginate customers for the back office, with the request counts the list
     * displays, so the table never queries per row.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginateForAdmin(CustomerFilters $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Load a customer with everything the detail screen shows.
     */
    public function loadForAdmin(Customer $customer): Customer;

    /**
     * Create the customer, or refresh the details of an existing one.
     *
     * Returning customers are matched on their phone number, which is the only
     * identifier the chatbot always collects.
     */
    public function updateOrCreate(CustomerData $data): Customer;
}
