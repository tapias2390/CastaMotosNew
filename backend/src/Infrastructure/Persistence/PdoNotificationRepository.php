<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\NotificationRepositoryInterface;
use PDO;

/**
 * Notificaciones en campanita (sección nueva, sin push real todavía —
 * ver PushNotificationFactory, que hoy solo loguea). "notifyAllUsers" y
 * "notifyAdmins" hacen fan-out por escritura: una fila por destinatario,
 * vía INSERT...SELECT en una sola consulta (no un loop en PHP por usuario).
 */
final class PdoNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function notifyAllUsers(string $type, string $title, string $message, ?array $data = null): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO notifications (user_id, type, title, message, data)
             SELECT id, :type, :title, :message, :data FROM users'
        );
        $stmt->execute([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function notifyAdmins(string $type, string $title, string $message, ?array $data = null): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO notifications (user_id, type, title, message, data)
             SELECT DISTINCT u.id, :type, :title, :message, :data
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.name IN ("administrador", "superadministrador")'
        );
        $stmt->execute([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function listForUser(int $userId, int $limit = 30): array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, type, title, message, data, read_at, created_at
             FROM notifications WHERE user_id = :user_id
             ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $notifications = array_map(static function (array $row): array {
            $row['data'] = $row['data'] !== null ? json_decode($row['data'], true) : null;
            $row['is_read'] = $row['read_at'] !== null;

            return $row;
        }, $rows);

        $countStmt = $this->connection->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL'
        );
        $countStmt->execute(['user_id' => $userId]);

        return [
            'notifications' => $notifications,
            'unread_count' => (int) $countStmt->fetchColumn(),
        ];
    }

    public function markRead(int $userId, int $notificationId): void
    {
        // WHERE user_id también (no solo id): un usuario nunca debe poder
        // marcar como leída una notificación ajena adivinando el id.
        $stmt = $this->connection->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE id = :id AND user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE notifications SET read_at = NOW() WHERE user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
    }
}
