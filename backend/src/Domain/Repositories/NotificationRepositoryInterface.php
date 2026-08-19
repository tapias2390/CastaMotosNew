<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface NotificationRepositoryInterface
{
    /** Crea una notificación para TODOS los usuarios (catálogo nuevo: productos/servicios/promos). */
    public function notifyAllUsers(string $type, string $title, string $message, ?array $data = null): void;

    /** Crea una notificación para los roles con permiso de gestión (administrador/superadministrador). */
    public function notifyAdmins(string $type, string $title, string $message, ?array $data = null): void;

    /** @return array{notifications: array<int, array<string, mixed>>, unread_count: int} */
    public function listForUser(int $userId, int $limit = 30): array;

    public function markRead(int $userId, int $notificationId): void;

    public function markAllRead(int $userId): void;
}
