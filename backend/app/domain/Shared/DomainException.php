<?php

declare(strict_types=1);

namespace App\domain\Shared;

abstract class DomainException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    abstract public function getErrorCode(): string;
}