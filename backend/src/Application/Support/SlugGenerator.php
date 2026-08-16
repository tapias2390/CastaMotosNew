<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Genera slugs legibles y únicos (secciones 10/13: "Slug" en productos y
 * categorías). Es una función pura; la unicidad se resuelve fuera (el
 * repositorio decide si el slug ya existe), para no acoplar esta clase a PDO.
 */
final class SlugGenerator
{
    public static function slugify(string $text): string
    {
        $text = self::stripAccents($text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

        return trim($text, '-');
    }

    /**
     * @param callable(string):bool $exists Devuelve true si el slug candidato ya existe.
     */
    public static function unique(string $text, callable $exists): string
    {
        $base = self::slugify($text) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($exists($slug)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Reemplazo explícito de acentos/eñes en vez de iconv//TRANSLIT: ese
     * transliterador se comporta distinto según el SO/locale (en Windows
     * puede dejar un carácter residual en vez de solo quitar el acento),
     * así que se prefiere un mapa fijo con resultado predecible.
     */
    private static function stripAccents(string $text): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ];

        return strtr($text, $map);
    }
}
