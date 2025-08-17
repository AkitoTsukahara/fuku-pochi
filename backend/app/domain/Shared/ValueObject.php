<?php

declare(strict_types=1);

namespace App\domain\Shared;

abstract class ValueObject
{
    public function equals(ValueObject $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    abstract public function toArray(): array;
}