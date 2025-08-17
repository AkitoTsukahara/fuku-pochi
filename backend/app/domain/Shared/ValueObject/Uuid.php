<?php

declare(strict_types=1);

namespace App\domain\Shared\ValueObject;

use App\domain\Shared\ValueObject;
use App\domain\Shared\Exception\InvalidValueException;

class Uuid extends ValueObject
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    public static function generate(): self
    {
        return new self(\Illuminate\Support\Str::uuid()->toString());
    }

    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidValueException('UUID', (string)$value, 'Must be a string');
        }

        if (!\Illuminate\Support\Str::isUuid($value)) {
            throw new InvalidValueException('UUID', $value, 'Must be a valid UUID v4 format');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return ['uuid' => $this->value];
    }

    public function __toString(): string
    {
        return $this->value;
    }
}