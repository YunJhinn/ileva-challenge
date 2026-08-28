<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Thrown when request input fails validation. Carries a field => message
 * map so the API can return a precise 422 response instead of a generic
 * "bad request".
 */
final class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed');
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
