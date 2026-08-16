<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Número de pedido legible: prefijo por fecha + sufijo aleatorio, con
 * verificación de unicidad (mismo patrón que SlugGenerator::unique()).
 */
final class OrderNumberGenerator
{
    /**
     * @param callable(string):bool $exists Devuelve true si el número candidato ya existe.
     */
    public static function generate(callable $exists): string
    {
        do {
            $candidate = 'CM' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($exists($candidate));

        return $candidate;
    }
}
