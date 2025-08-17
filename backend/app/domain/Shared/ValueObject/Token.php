<?php

declare(strict_types=1);

namespace App\domain\Shared\ValueObject;

use App\domain\Shared\ValueObject;
use App\domain\Shared\Exception\InvalidValueException;

class Token extends ValueObject
{
    private const LENGTH = 32;
    private const PATTERN = '/^[a-zA-Z0-9]{32}$/';

    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    public static function generate(): self
    {
        return new self(\Illuminate\Support\Str::random(self::LENGTH));
    }

    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidValueException('Token', (string)$value, 'Must be a string');
        }

        if (mb_strlen($value) !== self::LENGTH) {
            throw new InvalidValueException(
                'Token', 
                $value, 
                sprintf('Must be exactly %d characters long', self::LENGTH)
            );
        }

        if (!preg_match(self::PATTERN, $value)) {
            throw new InvalidValueException(
                'Token', 
                $value, 
                'Must contain only alphanumeric characters'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return ['token' => $this->value];
    }

    public function __toString(): string
    {
        return $this->value;
    }
}