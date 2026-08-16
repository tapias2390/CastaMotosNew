<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\FavoriteRepositoryInterface;
use PDO;

final class PdoFavoriteRepository implements FavoriteRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function add(int $userId, string $type, int $itemId): void
    {
        // Idempotente: si ya existe (mismo user+type+item), no falla ni duplica.
        $stmt = $this->connection->prepare(
            'INSERT INTO favorites (user_id, favoritable_type, favoritable_id)
             VALUES (:user_id, :type, :item_id)
             ON DUPLICATE KEY UPDATE user_id = user_id'
        );
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'item_id' => $itemId]);
    }

    public function remove(int $userId, string $type, int $itemId): void
    {
        $stmt = $this->connection->prepare(
            'DELETE FROM favorites WHERE user_id = :user_id AND favoritable_type = :type AND favoritable_id = :item_id'
        );
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'item_id' => $itemId]);
    }

    public function isFavorite(int $userId, string $type, int $itemId): bool
    {
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM favorites WHERE user_id = :user_id AND favoritable_type = :type AND favoritable_id = :item_id'
        );
        $stmt->execute(['user_id' => $userId, 'type' => $type, 'item_id' => $itemId]);

        return (bool) $stmt->fetchColumn();
    }

    public function listForUser(int $userId): array
    {
        $products = $this->connection->prepare(
            "SELECT f.id AS favorite_id, f.created_at AS favorited_at, 'product' AS type,
                    p.id, p.name, p.slug, p.price, p.status,
                    (SELECT url FROM product_images pi WHERE pi.product_id = p.id
                        ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS primary_image
             FROM favorites f
             INNER JOIN products p ON p.id = f.favoritable_id AND p.deleted_at IS NULL
             WHERE f.user_id = :user_id AND f.favoritable_type = 'product'"
        );
        $products->execute(['user_id' => $userId]);

        $services = $this->connection->prepare(
            "SELECT f.id AS favorite_id, f.created_at AS favorited_at, 'service' AS type,
                    s.id, s.name, s.slug, s.price, s.status,
                    (SELECT url FROM service_images si WHERE si.service_id = s.id
                        ORDER BY si.sort_order ASC LIMIT 1) AS primary_image
             FROM favorites f
             INNER JOIN services s ON s.id = f.favoritable_id AND s.deleted_at IS NULL
             WHERE f.user_id = :user_id AND f.favoritable_type = 'service'"
        );
        $services->execute(['user_id' => $userId]);

        $all = array_merge($products->fetchAll(), $services->fetchAll());

        usort($all, fn ($a, $b) => strcmp($b['favorited_at'], $a['favorited_at']));

        return $all;
    }
}
