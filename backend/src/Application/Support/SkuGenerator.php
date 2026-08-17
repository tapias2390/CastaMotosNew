<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Genera un SKU legible y único cuando el vendedor no escribe uno propio
 * (sección 10: "crear SKU dinámicamente y que no se repitan"). Prefijo con
 * las iniciales de la categoría (más fácil de reconocer en un listado que un
 * código totalmente aleatorio) + sufijo aleatorio, con el mismo patrón de
 * verificación de unicidad que SlugGenerator::unique() y OrderNumberGenerator.
 */
final class SkuGenerator
{
    /**
     * @param callable(string):bool $exists Devuelve true si el SKU candidato ya existe.
     */
    public static function generate(string $categoryName, callable $exists): string
    {
        $prefix = self::prefixFromCategory($categoryName);

        do {
            $candidate = $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($exists($candidate));

        return $candidate;
    }

    private static function prefixFromCategory(string $categoryName): string
    {
        $slug = SlugGenerator::slugify($categoryName);
        $letters = str_replace('-', '', $slug);
        $prefix = strtoupper(substr($letters, 0, 3));

        return $prefix !== '' ? $prefix : 'PRD';
    }
}
