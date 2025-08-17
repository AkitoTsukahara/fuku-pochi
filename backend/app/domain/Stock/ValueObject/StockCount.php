<?php

declare(strict_types=1);

namespace App\domain\Stock\ValueObject;

use App\domain\Shared\ValueObject;
use App\domain\Shared\Exception\InvalidValueException;

class StockCount extends ValueObject
{
    private const MIN_VALUE = 0;
    private const MAX_VALUE = 999;

    public function __construct(int $value)
    {
        parent::__construct($value);
    }

    protected function validate(mixed $value): void
    {
        if (!is_int($value)) {
            throw new InvalidValueException('StockCount', (string)$value, 'Must be an integer');
        }

        if ($value < self::MIN_VALUE || $value > self::MAX_VALUE) {
            throw new InvalidValueException(
                'StockCount', 
                (string)$value, 
                sprintf('Must be between %d and %d', self::MIN_VALUE, self::MAX_VALUE)
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function increment(): self
    {
        if ($this->value >= self::MAX_VALUE) {
            throw new InvalidValueException(
                'StockCount', 
                (string)($this->value + 1), 
                sprintf('Cannot exceed maximum value of %d', self::MAX_VALUE)
            );
        }
        
        return new self($this->value + 1);
    }

    public function decrement(): self
    {
        if ($this->value <= self::MIN_VALUE) {
            throw new InvalidValueException(
                'StockCount', 
                (string)($this->value - 1), 
                sprintf('Cannot go below minimum value of %d', self::MIN_VALUE)
            );
        }
        
        return new self($this->value - 1);
    }

    public function add(int $amount): self
    {
        $newValue = $this->value + $amount;
        if ($newValue > self::MAX_VALUE) {
            throw new InvalidValueException(
                'StockCount', 
                (string)$newValue, 
                sprintf('Result cannot exceed maximum value of %d', self::MAX_VALUE)
            );
        }
        
        return new self($newValue);
    }

    public function subtract(int $amount): self
    {
        $newValue = $this->value - $amount;
        if ($newValue < self::MIN_VALUE) {
            throw new InvalidValueException(
                'StockCount', 
                (string)$newValue, 
                sprintf('Result cannot go below minimum value of %d', self::MIN_VALUE)
            );
        }
        
        return new self($newValue);
    }

    public function isZero(): bool
    {
        return $this->value === self::MIN_VALUE;
    }

    public function isMaximum(): bool
    {
        return $this->value === self::MAX_VALUE;
    }

    public function toArray(): array
    {
        return ['stock_count' => $this->value];
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}