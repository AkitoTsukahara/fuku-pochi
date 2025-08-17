<?php

declare(strict_types=1);

namespace App\UseCase\Shared;

abstract class UseCase
{
    protected function validateInput($input): void
    {
        // Basic validation can be implemented here
        // More specific validation should be done in concrete classes
    }

    protected function handleSuccess($result): array
    {
        return [
            'success' => true,
            'data' => $result,
            'message' => 'Operation completed successfully'
        ];
    }

    protected function handleError(\Throwable $exception): array
    {
        return [
            'success' => false,
            'data' => null,
            'message' => $exception->getMessage(),
            'error_code' => $exception->getCode()
        ];
    }
}