<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\ReviewRepositoryInterface;
use PDO;

final class PdoReviewRepository implements ReviewRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function listApprovedForItem(string $type, int $itemId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT r.id, r.rating, r.comment, r.created_at,
                    u.name AS user_name, u.last_name AS user_last_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.reviewable_type = :type AND r.reviewable_id = :item_id AND r.status = "approved"
             ORDER BY r.created_at DESC'
        );
        $stmt->execute(['type' => $type, 'item_id' => $itemId]);

        return $stmt->fetchAll();
    }

    public function userHasPurchased(int $userId, string $type, int $itemId): bool
    {
        // "Solo quien compró puede reseñar" (sección 26): un pedido CANCELADO
        // no cuenta como compra real — mismo criterio que en otros lados de
        // este proyecto (ver hasBeenUsedByUser() de cupones).
        $column = $type === 'service' ? 'service_id' : 'product_id';

        $stmt = $this->connection->prepare(
            "SELECT 1 FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.user_id = :user_id AND oi.{$column} = :item_id AND o.status != 'CANCELADO'
             LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId, 'item_id' => $itemId]);

        return (bool) $stmt->fetchColumn();
    }

    public function userHasReviewed(int $userId, string $type, int $itemId): bool
    {
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM reviews WHERE user_id = :user_id AND reviewable_type = :type AND reviewable_id = :item_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'item_id' => $itemId]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(int $userId, string $type, int $itemId, int $rating, ?string $comment): int
    {
        // Se aprueba directo al crear (sin cola de moderación todavía): ya
        // está gateado por userHasPurchased() arriba, que es el filtro anti-
        // spam real. El estado "pending"/"rejected" del esquema queda listo
        // para el día que se agregue un panel de moderación en /admin.
        $stmt = $this->connection->prepare(
            "INSERT INTO reviews (user_id, reviewable_type, reviewable_id, rating, comment, status)
             VALUES (:user_id, :type, :item_id, :rating, :comment, 'approved')"
        );
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'item_id' => $itemId,
            'rating' => $rating,
            'comment' => $comment,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function recalculateRating(string $type, int $itemId): void
    {
        $agg = $this->connection->prepare(
            "SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS total
             FROM reviews WHERE reviewable_type = :type AND reviewable_id = :item_id AND status = 'approved'"
        );
        $agg->execute(['type' => $type, 'item_id' => $itemId]);
        $result = $agg->fetch();

        $table = $type === 'service' ? 'services' : 'products';
        $update = $this->connection->prepare(
            "UPDATE {$table} SET rating_avg = :avg, rating_count = :count WHERE id = :id"
        );
        $update->execute([
            'avg' => round((float) $result['avg_rating'], 2),
            'count' => (int) $result['total'],
            'id' => $itemId,
        ]);
    }
}
