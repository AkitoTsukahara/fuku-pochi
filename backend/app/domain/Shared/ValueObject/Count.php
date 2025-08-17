<?php

declare(strict_types=1);

namespace App\domain\Shared\ValueObject;

use App\domain\Shared\ValueObject;
use App\domain\Shared\Exception\InvalidValueException;

class Count extends ValueObject
{
    private const MIN_VALUE = 0;

    public function __construct(int $value)
    {
        parent::__construct($value);
    }

    protected function validate(mixed $value): void
    {
        if (!is_int($value)) {
            throw new InvalidValueException('Count', (string)$value, 'Must be an integer');
        }

        if ($value < self::MIN_VALUE) {
            throw new InvalidValueException(
                'Count', 
                (string)$value, 
                sprintf('Must be greater than or equal to %d', self::MIN_VALUE)
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function increment(): self
    {
        return new self($this->value + 1);
    }

    public function decrement(): self
    {
        if ($this->value === 0) {
            throw new InvalidValueException('Count', '0', 'Cannot decrement below zero');
        }
        
        return new self($this->value - 1);
    }

    public function add(int $amount): self
    {
        return new self($this->value + $amount);
    }

    public function subtract(int $amount): self
    {
        $newValue = $this->value - $amount;
        if ($newValue < self::MIN_VALUE) {
            throw new InvalidValueException(
                'Count', 
                (string)$newValue, 
                'Result cannot be negative'
            );
        }
        
        return new self($newValue);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function toArray(): array
    {
        return ['count' => $this->value];
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}