<?php

declare(strict_types=1);

namespace App\infra\Shared\Id;

use App\domain\Shared\Id\UuidGeneratorInterface;
use Illuminate\Support\Str;

class UuidGenerator implements UuidGeneratorInterface
{
    public function generate(): string
    {
        return Str::uuid()->toString();
    }

    public function isValid(string $uuid): bool
    {
        return Str::isUuid($uuid);
    }
}