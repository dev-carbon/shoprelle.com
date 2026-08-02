<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\CustomerFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerDetailResource;
use App\Http\Resources\CustomerListResource;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerRepository $customers,
    ) {}

    /**
     * List every customer who has ever submitted a request.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Customer::class);

        $filters = CustomerFilters::fromRequest($request);

        return Inertia::render('admin/customers/index', [
            'customers' => CustomerListResource::collection(
                $this->customers->paginateForAdmin($filters),
            ),
            'filters' => $filters->toArray(),
            'countries' => config('shoprelle.countries'),
        ]);
    }

    /**
     * Show one customer with their full request history.
     */
    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('admin/customers/show', [
            'customer' => new CustomerDetailResource(
                $this->customers->loadForAdmin($customer),
            ),
        ]);
    }
}
