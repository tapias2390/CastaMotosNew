<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Domain\Entities\User;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoNotificationRepository;

/**
 * Campanita de notificaciones (header, todo el sitio): cada usuario logueado
 * ve solo las suyas. El fan-out (una fila por usuario) ya se hizo al crear
 * el producto/servicio/pedido — ver ProductController/ServiceController/
 * CheckoutUseCase — así que acá es una simple lectura por user_id.
 */
final class NotificationController
{
    private PdoNotificationRepository $notifications;

    public function __construct()
    {
        $this->notifications = new PdoNotificationRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        Response::success($this->notifications->listForUser($user->id));
    }

    public function markRead(Request $request, string $id): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        $this->notifications->markRead($user->id, (int) $id);

        Response::success(null, 'Notificación marcada como leída.');
    }

    public function markAllRead(Request $request): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        $this->notifications->markAllRead($user->id);

        Response::success(null, 'Todas las notificaciones marcadas como leídas.');
    }
}
