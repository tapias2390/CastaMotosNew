<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Support\OrderStatusTransitions;
use App\Application\UseCases\Orders\ChangeOrderStatusUseCase;
use App\Application\Validation\Validator;
use App\Exceptions\NotFoundException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoOrderRepository;
use App\Infrastructure\Persistence\PdoPushSubscriptionRepository;

/**
 * Gestión administrativa de pedidos (sección 22, permiso manage-orders).
 * Distinto de CheckoutController::show (Fase 5), que solo deja ver el pedido
 * al propio dueño.
 */
final class AdminOrderController
{
    private PdoOrderRepository $orders;
    private PdoPushSubscriptionRepository $pushSubscriptions;

    public function __construct()
    {
        $connection = Connection::get();
        $this->orders = new PdoOrderRepository($connection);
        $this->pushSubscriptions = new PdoPushSubscriptionRepository($connection);
    }

    public function index(Request $request): void
    {
        $result = $this->orders->paginateForAdmin($request->query());
        $result['data'] = array_map([$this, 'withNextStatuses'], $result['data']);

        Response::success($result);
    }

    public function show(Request $request, string $orderNumber): void
    {
        $order = $this->orders->findByOrderNumberForAdmin($orderNumber);
        if ($order === null) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        Response::success($this->withNextStatuses($order));
    }

    /** Agrega a qué estados se puede avanzar desde el actual (sección 22: sin esto,
     * el frontend tendría que duplicar el grafo de OrderStatusTransitions). */
    private function withNextStatuses(array $order): array
    {
        $order['next_statuses'] = OrderStatusTransitions::nextStates($order['status']);

        return $order;
    }

    public function updateStatus(Request $request, string $orderNumber): void
    {
        $data = Validator::make($request->input(), [
            'status' => 'required|in:' . implode(',', OrderStatusTransitions::allStatuses()),
            'comment' => 'max:500',
        ])->validate();

        /** @var \App\Domain\Entities\User $user */
        $user = $request->attribute('auth_user');

        $useCase = new ChangeOrderStatusUseCase($this->orders, $this->pushSubscriptions);
        $order = $useCase->handle($orderNumber, $data['status'], $data['comment'] ?? null, $user->id);

        Response::success($order, 'Estado del pedido actualizado.');
    }
}
