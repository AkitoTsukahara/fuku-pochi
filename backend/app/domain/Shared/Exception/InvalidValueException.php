<?php

declare(strict_types=1);

namespace App\domain\Shared\Exception;

use App\domain\Shared\DomainException;

class InvalidValueException extends DomainException
{
    public function __construct(string $valueType, string $invalidValue, string $reason = '')
    {
        $message = "Invalid {$valueType}: '{$invalidValue}'";
        if ($reason !== '') {
            $message .= " - {$reason}";
        }
        
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'INVALID_VALUE';
    }
}