<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface OrderRepositoryInterface
{
    public function existsByOrderNumber(string $orderNumber): bool;

    /**
     * @return array|null Incluye "items" (order_items) para la confirmación.
     */
    public function findByOrderNumberForUser(string $orderNumber, int $userId): ?array;

    /**
     * Ejecuta la transacción completa del checkout: vuelve a bloquear y
     * verificar el stock real (SELECT ... FOR UPDATE) justo antes de
     * descontarlo, crea el pedido + ítems + historial + pago inicial, y deja
     * el carrito vacío — todo o nada (sección 35).
     *
     * @throws \App\Exceptions\ValidationException si algún producto ya no
     *         tiene stock suficiente en el momento de confirmar.
     *
     * @return array{id:int, order_number:string}
     */
    public function createFromCheckout(array $order): array;
}
