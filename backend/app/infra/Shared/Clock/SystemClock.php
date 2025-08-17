<?php

declare(strict_types=1);

namespace App\infra\Shared\Clock;

use App\domain\Shared\Clock\ClockInterface;

class SystemClock implements ClockInterface
{
    private string $timezone;

    public function __construct(string $timezone = 'Asia/Tokyo')
    {
        $this->timezone = $timezone;
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone($this->timezone));
    }

    public function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today', new \DateTimeZone($this->timezone));
    }

    public function format(\DateTimeImmutable $dateTime, string $format): string
    {
        return $dateTime->format($format);
    }
}