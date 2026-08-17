<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\PushSubscriptionRepositoryInterface;
use PDO;

final class PdoPushSubscriptionRepository implements PushSubscriptionRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function subscribe(int $userId, string $token, string $platform): void
    {
        // Un mismo token solo puede pertenecer a un usuario a la vez (ej. si
        // alguien cierra sesión y otra persona inicia sesión en el mismo
        // navegador) — "ON DUPLICATE" reasigna el dueño en vez de duplicar.
        $stmt = $this->connection->prepare(
            'INSERT INTO push_subscriptions (user_id, token, platform)
             VALUES (:user_id, :token, :platform)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), platform = VALUES(platform)'
        );
        $stmt->execute(['user_id' => $userId, 'token' => $token, 'platform' => $platform]);
    }

    public function unsubscribe(int $userId, string $token): void
    {
        $stmt = $this->connection->prepare(
            'DELETE FROM push_subscriptions WHERE user_id = :user_id AND token = :token'
        );
        $stmt->execute(['user_id' => $userId, 'token' => $token]);
    }

    public function tokensForUser(int $userId): array
    {
        $stmt = $this->connection->prepare('SELECT token FROM push_subscriptions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
