<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

/**
 * Contrato de middleware: recibe el Request y devuelve uno (posiblemente
 * enriquecido, ej. con el usuario autenticado) o lanza una excepción de
 * App\Exceptions si la petición no debe continuar (401/403/etc.).
 */
interface Middleware
{
    public function handle(Request $request): Request;
}
