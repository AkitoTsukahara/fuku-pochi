<?php

declare(strict_types=1);

namespace App\domain\Shared\Repository;

class PageResult
{
    private array $items;
    private int $total;
    private int $page;
    private int $perPage;

    public function __construct(array $items, int $total, int $page, int $perPage)
    {
        $this->items = $items;
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    public function hasNext(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }
}