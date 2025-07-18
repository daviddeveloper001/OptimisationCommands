<?php

namespace App\Services\Api\V1;

use App\Models\Customer;
use App\Exceptions\CustomerException;
use App\Repositories\V1\CustomerRepositoryV1;
use Illuminate\Http\Response;

class CustomerServiceV1
{
    public function __construct(private CustomerRepositoryV1 $customerRepository) {}

    public function getAllCustomers($filters, $perPage)
    {
        try {
            return Customer::filter($filters)->paginate($perPage);
        } catch (\Exception $e) {
            throw new CustomerException(
                'Failed to retrieve Customers',
                developerHint: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $e
            );
        }
    }

    public function getCustomerById(Customer $customer)
    {
        try {
            $result = $this->customerRepository->find($customer);
            if (!$result) {
                throw new CustomerException('Customer not found', Response::HTTP_NOT_FOUND);
            }
            return $result;
        } catch (\Exception $e) {
            throw new CustomerException(
                'Failed to retrieve Customer',
                developerHint: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $e
            );
        }
    }

    public function createCustomer(array $data)
    {
        try {
            return $this->customerRepository->create($data);
        } catch (\Exception $e) {
            throw new CustomerException(
                'Failed to create Customer',
                developerHint: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $e
            );
        }
    }

    public function updateCustomer(Customer $customer, array $data)
    {
        try {
            return $this->customerRepository->update($customer, $data);
        } catch (\Exception $e) {
            throw new CustomerException(
                'Failed to update Customer',
                developerHint: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $e
            );
        }
    }

    public function deleteCustomer(Customer $customer)
    {
        try {
            return $this->customerRepository->delete($customer);
        } catch (\Exception $e) {
            throw new CustomerException(
                'Failed to delete Customer',
                developerHint: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                previous: $e
            );
        }
    }
}