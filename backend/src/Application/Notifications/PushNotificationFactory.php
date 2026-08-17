<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Infrastructure\Config\Config;

/**
 * Resuelve el proveedor de push configurado (PUSH_PROVIDER, sección 24 —
 * mismo patrón que AI_PROVIDER/PAYMENT_PROVIDER de la Fase 1). Hoy solo
 * existe LoggingPushProvider porque no hay ningún proveedor real conectado;
 * agregar Firebase/OneSignal en el futuro es sumar un "case" acá, sin tocar
 * quién llama a PushNotificationInterface::send().
 */
final class PushNotificationFactory
{
    public static function make(): PushNotificationInterface
    {
        return match ((string) Config::get('app.push.provider', '')) {
            default => new LoggingPushProvider(),
        };
    }
}
