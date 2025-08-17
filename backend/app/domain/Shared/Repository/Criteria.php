<?php

declare(strict_types=1);

namespace App\domain\Shared\Repository;

interface Criteria
{
    public function getFilters(): array;

    public function getSortBy(): ?string;

    public function getSortOrder(): string;

    public function getLimit(): ?int;

    public function getOffset(): ?int;
}