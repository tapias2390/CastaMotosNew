<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Excepción base de la aplicación. Todas las excepciones de negocio
 * deben extender de esta clase para que el Kernel las traduzca a
 * una respuesta HTTP consistente.
 */
class AppException extends Exception
{
    protected int $statusCode = 500;
    protected array $errors = [];

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
