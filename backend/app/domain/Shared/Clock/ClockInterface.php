<?php

declare(strict_types=1);

namespace App\domain\Shared\Clock;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;

    public function today(): \DateTimeImmutable;

    public function format(\DateTimeImmutable $dateTime, string $format): string;
}