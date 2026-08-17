<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoPushSubscriptionRepository;

/**
 * Registro de dispositivos para notificaciones push (sección 24). Requiere
 * sesión: la suscripción siempre queda ligada a un usuario, nunca anónima.
 */
final class PushSubscriptionController
{
    private PdoPushSubscriptionRepository $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new PdoPushSubscriptionRepository(Connection::get());
    }

    public function subscribe(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'token' => 'required|max:500',
            'platform' => 'max:20',
        ])->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        $this->subscriptions->subscribe($user->id, $data['token'], $data['platform'] ?? 'web');

        Response::success(null, 'Dispositivo suscrito correctamente.', 201);
    }

    public function unsubscribe(Request $request): void
    {
        $data = Validator::make($request->input(), ['token' => 'required'])->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        $this->subscriptions->unsubscribe($user->id, $data['token']);

        Response::success(null, 'Dispositivo desuscrito correctamente.');
    }
}
