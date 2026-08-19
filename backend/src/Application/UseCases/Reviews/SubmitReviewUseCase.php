<?php

declare(strict_types=1);

namespace App\Application\UseCases\Reviews;

use App\Domain\Repositories\ReviewRepositoryInterface;
use App\Exceptions\ValidationException;

final class SubmitReviewUseCase
{
    public function __construct(private ReviewRepositoryInterface $reviews)
    {
    }

    public function handle(int $userId, string $type, int $itemId, int $rating, ?string $comment): int
    {
        // "Solo quien compró puede reseñar" (sección 26) — se valida acá, no
        // se confía en que el frontend solo muestre el formulario a quien
        // corresponde (cualquiera podría llamar al endpoint directo).
        if (!$this->reviews->userHasPurchased($userId, $type, $itemId)) {
            throw new ValidationException('No fue posible publicar la reseña.', [
                'item' => ['Solo puedes reseñar productos o servicios que hayas comprado.'],
            ]);
        }

        if ($this->reviews->userHasReviewed($userId, $type, $itemId)) {
            throw new ValidationException('No fue posible publicar la reseña.', [
                'item' => ['Ya dejaste una reseña para este producto o servicio.'],
            ]);
        }

        $reviewId = $this->reviews->create($userId, $type, $itemId, $rating, $comment);
        $this->reviews->recalculateRating($type, $itemId);

        return $reviewId;
    }
}
