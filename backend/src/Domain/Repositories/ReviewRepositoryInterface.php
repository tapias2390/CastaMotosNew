<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Reseñas polimórficas de producto/servicio (sección 26, tabla `reviews`
 * de la Fase 1 — sin ningún endpoint hasta ahora). "Solo quien compró
 * puede reseñar" se valida acá (userHasPurchased), no en el esquema.
 */
interface ReviewRepositoryInterface
{
    /** @return array Reseñas aprobadas del ítem, más recientes primero, con el nombre del autor. */
    public function listApprovedForItem(string $type, int $itemId): array;

    public function userHasPurchased(int $userId, string $type, int $itemId): bool;

    public function userHasReviewed(int $userId, string $type, int $itemId): bool;

    public function create(int $userId, string $type, int $itemId, int $rating, ?string $comment): int;

    /**
     * Recalcula rating_avg/rating_count del producto o servicio a partir de
     * sus reseñas aprobadas — se llama justo después de create() para que
     * las estrellas que ya se muestran en todo el sitio (helpers.renderStars)
     * dejen de estar siempre vacías.
     */
    public function recalculateRating(string $type, int $itemId): void;
}
