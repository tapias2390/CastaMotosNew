<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Gestión de inventario (sección 25). "Disponible" siempre se calcula como
 * stock_current - stock_reserved.
 */
interface InventoryRepositoryInterface
{
    /**
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function listWithProductInfo(array $filters): array;

    /**
     * Ajuste manual de stock (entrada/salida/corrección), con motivo
     * obligatorio (auditoría, sección 36). Actualiza products.stock e
     * inventory.stock_current, y registra inventory_movements — todo en
     * una transacción.
     *
     * @throws \App\Exceptions\ValidationException si el ajuste dejaría stock negativo.
     */
    public function adjust(int $productId, string $type, int $quantity, string $reason, int $userId): void;

    /**
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function movements(array $filters): array;
}
