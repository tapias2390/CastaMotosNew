<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Infrastructure\Logging\Logger;

/**
 * Implementación por defecto mientras PUSH_PROVIDER esté vacío (sección 24):
 * en vez de simular un envío que nunca llegó a ningún dispositivo real, deja
 * constancia en el log de qué se habría enviado — mismo criterio honesto que
 * ExternalPaymentGateway con las pasarelas de pago sin credenciales.
 * Cuando exista un proveedor real (Firebase Cloud Messaging, OneSignal...),
 * esta clase se reemplaza por una que sí llama a su API — el resto de la
 * aplicación no cambia, porque solo conoce PushNotificationInterface.
 */
final class LoggingPushProvider implements PushNotificationInterface
{
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        Logger::info('Push notification (sin proveedor configurado, solo registrada)', [
            'tokens_count' => count($tokens),
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
