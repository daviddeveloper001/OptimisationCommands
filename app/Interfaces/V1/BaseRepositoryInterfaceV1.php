<?php

namespace App\Interfaces\V1;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterfaceV1
{
    public function all();
    public function find(Model $model);
    public function findBy(int $id);
    public function create(array $data);
    public function update(Model $model, array $data);
    public function delete(Model $model);
}