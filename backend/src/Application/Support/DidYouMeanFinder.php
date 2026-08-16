<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Corrección de términos simple (sección 14) usando levenshtein() nativo de
 * PHP, sin dependencias externas. Es una función pura: recibe el término y
 * la lista de nombres candidatos (la consulta a la base de datos vive en
 * quien la use), y devuelve el candidato más parecido si está lo bastante
 * cerca como para ser útil.
 */
final class DidYouMeanFinder
{
    private const MAX_DISTANCE_RATIO = 0.4; // hasta ~40% de la longitud del término

    /**
     * @param string[] $candidates
     */
    public static function find(string $term, array $candidates): ?string
    {
        $term = mb_strtolower(trim($term));
        if ($term === '' || empty($candidates)) {
            return null;
        }

        $maxDistance = max(1, (int) round(mb_strlen($term) * self::MAX_DISTANCE_RATIO));

        $best = null;
        $bestDistance = $maxDistance + 1;

        foreach (array_unique($candidates) as $candidate) {
            $normalized = mb_strtolower($candidate);

            if ($normalized === $term) {
                return null; // Coincidencia exacta: no hace falta sugerir nada.
            }

            // levenshtein() de PHP no soporta multibyte de forma nativa; para nombres
            // de catálogo (mayormente ASCII/latin-1 tras SlugGenerator-like uso normal)
            // es una aproximación suficiente para esta corrección "amable", no crítica.
            $distance = levenshtein($term, $normalized);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $bestDistance <= $maxDistance ? $best : null;
    }
}
