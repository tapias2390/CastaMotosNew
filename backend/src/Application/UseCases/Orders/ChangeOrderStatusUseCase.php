<?php

declare(strict_types=1);

namespace App\Application\UseCases\Orders;

use App\Application\Notifications\PushNotificationFactory;
use App\Application\Support\OrderStatusTransitions;
use App\Domain\Repositories\OrderRepositoryInterface;
use App\Domain\Repositories\PushSubscriptionRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Mail\EmailTemplates;
use App\Infrastructure\Mail\Mailer;

/**
 * Cambia el estado de un pedido (sección 22). La validación de la transición
 * en sí (¿se puede pasar de X a Y?) y el efecto sobre el stock reservado
 * viven en PdoOrderRepository::updateStatus(), dentro de la misma transacción
 * que el UPDATE — evita una condición de carrera entre validar aquí y
 * ejecutar allá. Este UseCase solo resuelve el pedido y valida que el
 * estado solicitado exista.
 */
final class ChangeOrderStatusUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private ?PushSubscriptionRepositoryInterface $pushSubscriptions = null
    ) {
    }

    public function handle(string $orderNumber, string $newStatus, ?string $comment, int $changedByUserId): array
    {
        $order = $this->orders->findByOrderNumberForAdmin($orderNumber);
        if ($order === null) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        if (!in_array($newStatus, OrderStatusTransitions::allStatuses(), true)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'status' => ['Estado no reconocido.'],
            ]);
        }

        $this->orders->updateStatus((int) $order['id'], $newStatus, $comment, $changedByUserId);

        $updatedOrder = $this->orders->findByOrderNumberForAdmin($orderNumber);

        // "Se confirma pedido / pago / preparación / en camino / entregado /
        // cancelado" (sección 23). Igual que en el checkout: nunca debe
        // tumbar el cambio de estado si el correo falla, ya se guardó en BD.
        $this->sendStatusEmail($updatedOrder);
        $this->sendStatusPush($updatedOrder);

        return $updatedOrder;
    }

    private function sendStatusPush(array $order): void
    {
        if ($this->pushSubscriptions === null) {
            return;
        }

        try {
            $tokens = $this->pushSubscriptions->tokensForUser((int) $order['user_id']);
            PushNotificationFactory::make()->send(
                $tokens,
                'Cambio de estado',
                "Tu pedido {$order['order_number']} cambió a: {$order['status']}.",
                ['order_number' => $order['order_number'], 'status' => $order['status']]
            );
        } catch (\Throwable $e) {
            Logger::error('Fallo al enviar push de cambio de estado', ['order_number' => $order['order_number'], 'error' => $e->getMessage()]);
        }
    }

    private function sendStatusEmail(array $order): void
    {
        try {
            $customerName = trim($order['customer_name'] . ' ' . $order['customer_last_name']);
            $orderUrl = rtrim((string) Config::get('app.url'), '/') . '/pedido/' . $order['order_number'];

            $content = EmailTemplates::orderStatusEmail(
                $order['status'],
                $customerName,
                $order['order_number'],
                (float) $order['total'],
                $orderUrl
            );

            if ($content === null) {
                return; // estado sin correo asociado en la sección 23 (ej. PAGO_PENDIENTE)
            }

            Mailer::send($order['customer_email'], $content['subject'], $content['html'], 'order_status_' . strtolower($order['status']), (int) $order['user_id']);
        } catch (\Throwable $e) {
            Logger::error('Fallo al enviar correo de cambio de estado', ['order_number' => $order['order_number'], 'error' => $e->getMessage()]);
        }
    }
}
