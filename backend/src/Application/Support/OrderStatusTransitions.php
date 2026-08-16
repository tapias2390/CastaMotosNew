<?php

declare(strict_types=1);

namespace App\Application\Support;

/**
 * Máquina de estados del pedido (sección 22). Función pura: no toca la BD,
 * solo decide qué transiciones son válidas y qué efecto tienen sobre el stock.
 */
final class OrderStatusTransitions
{
    private const GRAPH = [
        'PENDIENTE' => ['CONFIRMADO', 'PAGO_PENDIENTE', 'CANCELADO'],
        'CONFIRMADO' => ['PAGO_PENDIENTE', 'PAGO_CONFIRMADO', 'CANCELADO'],
        'PAGO_PENDIENTE' => ['PAGO_CONFIRMADO', 'CANCELADO'],
        'PAGO_CONFIRMADO' => ['PREPARANDO', 'CANCELADO'],
        'PREPARANDO' => ['EN_CAMINO', 'CANCELADO'],
        'EN_CAMINO' => ['ENTREGADO', 'DEVUELTO'],
        'ENTREGADO' => ['DEVUELTO'],
        'CANCELADO' => [],
        'DEVUELTO' => [],
    ];

    /** Estados que liberan la reserva de stock (sección 25): cualquier estado terminal. */
    private const TERMINAL = ['ENTREGADO', 'CANCELADO', 'DEVUELTO'];

    /** Estados terminales en los que la venta NO se concretó: se restituye products.stock. */
    private const RESTORES_STOCK = ['CANCELADO', 'DEVUELTO'];

    public static function isAllowed(string $from, string $to): bool
    {
        return in_array($to, self::GRAPH[$from] ?? [], true);
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL, true);
    }

    public static function restoresStock(string $status): bool
    {
        return in_array($status, self::RESTORES_STOCK, true);
    }

    /**
     * @return string[] Todos los estados válidos (para validar en el Validator/Swagger).
     */
    public static function allStatuses(): array
    {
        return array_keys(self::GRAPH);
    }
}
