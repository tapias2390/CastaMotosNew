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
     * descontarlo, crea el pedido + ítems + historial + pago inicial, reserva
     * stock (sección 25) y deja el carrito vacío — todo o nada (sección 35).
     *
     * @throws \App\Exceptions\ValidationException si algún producto ya no
     *         tiene stock suficiente en el momento de confirmar.
     *
     * @return array{id:int, order_number:string}
     */
    public function createFromCheckout(array $order): array;

    /**
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function paginateForAdmin(array $filters): array;

    /**
     * Igual que findByOrderNumberForUser() pero sin filtrar por dueño (uso
     * administrativo, protegido por permiso manage-orders en el controller).
     */
    public function findByOrderNumberForAdmin(string $orderNumber): ?array;

    /**
     * Reservas de servicios (sección 12): items de pedido con servicio
     * agendado, con datos del cliente y del pedido — cada uno ES una reserva.
     *
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function paginateReservationsForAdmin(array $filters): array;

    public function findStatusById(int $orderId): ?string;

    /**
     * Cambia el estado del pedido dentro de una transacción: inserta en
     * order_status_history y, si el nuevo estado es terminal, libera la
     * reserva de stock (y la restituye a products.stock si la venta no se
     * concretó — CANCELADO/DEVUELTO). Ver OrderStatusTransitions.
     */
    public function updateStatus(int $orderId, string $newStatus, ?string $comment, int $changedByUserId): void;
}
