<?php

namespace App\Repositories\V1;

use App\Models\Customer;
use App\Repositories\V1\BaseRepositoryV1;

class CustomerRepositoryV1 extends BaseRepositoryV1
{
    const RELATIONS = [];

    public function __construct(Customer $customer)
    {
        parent::__construct($customer, self::RELATIONS);
    }
}