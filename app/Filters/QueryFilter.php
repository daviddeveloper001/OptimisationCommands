<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected Builder $builder;
    protected Request $request;
    protected array $sortable = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->all() as $key => $value) {
            if (method_exists($this, $key)) {
                $this->$key($value);
            }
        }

        return $builder;
    }

    protected function filter(array $arr): Builder
    {
        foreach ($arr as $key => $value) {
            if (method_exists($this, $key)) {
                $this->$key($value);
            }
        }

        return $this->builder;
    }

    protected function sort(string $value): void
    {
        $sortAttributes = explode(',', $value);

        foreach ($sortAttributes as $sortAttribute) {
            $direction = 'asc';

            if (strpos($sortAttribute, '-') === 0) {
                $direction = 'desc';
                $sortAttribute = substr($sortAttribute, 1);
            }

            if (!in_array($sortAttribute, $this->sortable) && !array_key_exists($sortAttribute, $this->sortable)) {
                continue;
            }

            $columnName = $this->sortable[$sortAttribute] ?? $sortAttribute;

            $this->builder->orderBy($columnName, $direction);
        }
    }
}