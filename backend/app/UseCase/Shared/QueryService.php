<?php

declare(strict_types=1);

namespace App\UseCase\Shared;

abstract class QueryService
{
    protected function validateQueryParameters($parameters): void
    {
        // Basic query parameter validation can be implemented here
        // More specific validation should be done in concrete classes
    }

    protected function formatResult($data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => 'Query executed successfully'
        ];
    }

    protected function handleQueryError(\Throwable $exception): array
    {
        return [
            'success' => false,
            'data' => null,
            'message' => $exception->getMessage(),
            'error_code' => $exception->getCode()
        ];
    }
}