<?php

declare(strict_types=1);

namespace App\domain\Shared\ValueObject;

use App\domain\Shared\ValueObject;
use App\domain\Shared\Exception\InvalidValueException;

class Name extends ValueObject
{
    private const MIN_LENGTH = 1;
    private const MAX_LENGTH = 100;

    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidValueException('Name', (string)$value, 'Must be a string');
        }

        $trimmed = trim($value);
        
        if (empty($trimmed)) {
            throw new InvalidValueException('Name', $value, 'Cannot be empty or whitespace only');
        }

        $length = mb_strlen($trimmed);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidValueException(
                'Name', 
                $value, 
                sprintf('Length must be between %d and %d characters', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }
    }

    public function value(): string
    {
        return trim($this->value);
    }

    public function toArray(): array
    {
        return ['name' => $this->value()];
    }

    public function __toString(): string
    {
        return $this->value();
    }
}