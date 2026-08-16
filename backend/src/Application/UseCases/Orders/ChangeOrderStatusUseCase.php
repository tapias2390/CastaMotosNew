<?php

declare(strict_types=1);

namespace App\Application\UseCases\Orders;

use App\Application\Support\OrderStatusTransitions;
use App\Domain\Repositories\OrderRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

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
    public function __construct(private OrderRepositoryInterface $orders)
    {
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

        return $this->orders->findByOrderNumberForAdmin($orderNumber);
    }
}
