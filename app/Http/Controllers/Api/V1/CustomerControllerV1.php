<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Filters\CustomerFilter;
use App\Services\Api\V1\CustomerServiceV1;
use App\Http\Controllers\Api\V1\ApiControllerV1;
use App\Http\Resources\Api\V1\Customer\CustomerResourceV1;
use App\Http\Requests\Api\V1\Customer\StoreCustomerRequestV1;
use App\Http\Requests\Api\V1\Customer\UpdateCustomerRequestV1;

class CustomerControllerV1 extends ApiControllerV1
{
    public function __construct(private CustomerServiceV1 $customerService) {}

    public function index(CustomerFilter $filters)
    {
        try {
            $perPage = request()->input('per_page', 10);
            $customers = $this->customerService->getAllCustomers($filters, $perPage);

            return $this->ok('Customers retrieved successfully', CustomerResourceV1::collection($customers));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(StoreCustomerRequestV1 $request)
    {
        try {
            $customer = $this->customerService->createCustomer($request->validated());
            return $this->ok('Customer created successfully', new CustomerResourceV1($customer));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(Customer $customer)
    {
        try {
            return $this->ok('Customer retrieved successfully', new CustomerResourceV1($customer));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(UpdateCustomerRequestV1 $request, Customer $customer)
    {
        try {
            $customer = $this->customerService->updateCustomer($customer, $request->validated());
            return $this->ok('Customer updated successfully', new CustomerResourceV1($customer));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Customer $customer)
    {
        try {
            $this->customerService->deleteCustomer($customer);
            return $this->ok('Customer deleted successfully');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}