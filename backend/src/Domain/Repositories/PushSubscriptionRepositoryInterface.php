<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Dispositivos/tokens suscritos a notificaciones push (sección 24). Un
 * usuario puede tener varios (celular + navegador, por ejemplo).
 */
interface PushSubscriptionRepositoryInterface
{
    public function subscribe(int $userId, string $token, string $platform): void;

    public function unsubscribe(int $userId, string $token): void;

    /** @return string[] Tokens activos de un usuario. */
    public function tokensForUser(int $userId): array;
}
