<?php

declare(strict_types=1);

namespace App\domain\Shared\Id;

interface UuidGeneratorInterface
{
    public function generate(): string;

    public function isValid(string $uuid): bool;
}