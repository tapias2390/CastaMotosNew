<?php

declare(strict_types=1);

namespace App\Application\Notifications;

/**
 * Puerto de notificaciones push (sección 24: "preparar arquitectura para
 * notificaciones push"). Mismo criterio que PaymentGatewayInterface — el
 * resto de la aplicación nunca habla con Firebase/OneSignal/etc.
 * directamente, siempre a través de esta interfaz.
 */
interface PushNotificationInterface
{
    /**
     * @param string[] $tokens Tokens de push_subscriptions (uno por dispositivo).
     * @param array<string,mixed> $data Datos extra para deep-linking (ej. order_number).
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void;
}
