<?php

declare(strict_types=1);

namespace App\infra\Shared\Repository;

use Illuminate\Database\Eloquent\Model;
use App\domain\Shared\Repository\Criteria;
use App\domain\Shared\Repository\PageResult;

abstract class EloquentRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function applyCriteria($query, Criteria $criteria)
    {
        // Apply filters
        foreach ($criteria->getFilters() as $field => $value) {
            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        // Apply sorting
        if ($criteria->getSortBy()) {
            $query->orderBy($criteria->getSortBy(), $criteria->getSortOrder());
        }

        // Apply pagination
        if ($criteria->getOffset()) {
            $query->offset($criteria->getOffset());
        }

        if ($criteria->getLimit()) {
            $query->limit($criteria->getLimit());
        }

        return $query;
    }

    protected function createPageResult(array $items, int $total, int $page, int $perPage): PageResult
    {
        return new PageResult($items, $total, $page, $perPage);
    }

    public function beginTransaction(): void
    {
        \DB::beginTransaction();
    }

    public function commit(): void
    {
        \DB::commit();
    }

    public function rollback(): void
    {
        \DB::rollback();
    }
}