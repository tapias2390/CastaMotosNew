<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    protected int $statusCode = 422;

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }
}
