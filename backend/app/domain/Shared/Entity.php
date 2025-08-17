<?php

declare(strict_types=1);

namespace App\domain\Shared;

abstract class Entity
{
    public function equals(Entity $other): bool
    {
        return $this->getId() === $other->getId();
    }

    abstract public function getId(): string;
}