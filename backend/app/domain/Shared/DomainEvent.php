<?php

declare(strict_types=1);

namespace App\domain\Shared;

abstract class DomainEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    abstract public function getEventName(): string;

    abstract public function toArray(): array;
}