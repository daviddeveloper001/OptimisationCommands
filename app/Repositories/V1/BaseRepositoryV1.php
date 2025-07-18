<?php

namespace App\Repositories\V1;

use Illuminate\Database\Eloquent\Model;
use App\Interfaces\V1\BaseRepositoryInterfaceV1;

class BaseRepositoryV1 implements BaseRepositoryInterfaceV1
{
    protected $model;
    protected $relations = [];

    public function __construct(Model $model, array $relations = [])
    {
        $this->model = $model;
        $this->relations = $relations;
    }

    public function all()
    {
        $query = $this->model->latest();
        if (!empty($this->relations)) {
            $query->with($this->relations);
        }
        return $query->get();
    }

    public function find(Model $model)
    {
        $query = $this->model;
        if (!empty($this->relations)) {
            $query->with($this->relations);
        }
        return $query->find($model);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data)
    {
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function delete(Model $model)
    {
        return $model->delete();
    }

    public function findBy(int $id)
    {
        return $this->model->find($id);
    }
}