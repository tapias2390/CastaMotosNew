<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Cupones de descuento (sección 30: porcentuales/fijos, fechas, límite de
 * uso, compra mínima). Aplican al carrito completo (no a productos o
 * categorías específicas) — ese alcance más fino queda para una fase
 * posterior si hace falta; los descuentos por producto/categoría ya existen
 * desde la Fase 3 vía products.discount_percentage.
 */
interface CouponRepositoryInterface
{
    public function findByCode(string $code): ?array;

    public function find(int $id): ?array;

    /** @return array{data: array, total: int, page: int, per_page: int} */
    public function paginateForAdmin(array $filters): array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function existsByCode(string $code, ?int $excludeId = null): bool;

    /** Se llama dentro de la transacción del checkout, junto con el resto del pedido. */
    public function incrementUsage(int $id): void;
}
