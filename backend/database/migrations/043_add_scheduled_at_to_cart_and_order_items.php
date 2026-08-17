<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Módulo de reservas de servicios (sección 12): a diferencia de un producto,
 * un servicio se agenda para una fecha/hora concreta. En vez de un sistema
 * paralelo, se extiende el carrito/checkout ya existente (Fase 5) con una
 * columna opcional — solo aplica a items de servicio, siempre NULL en
 * items de producto. El pedido resultante ES la reserva: su order_number,
 * estado y ciclo de vida (sección 22) ya están completos y probados.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec('ALTER TABLE cart_items ADD COLUMN scheduled_at DATETIME NULL AFTER service_id');
        $connection->exec('ALTER TABLE order_items ADD COLUMN scheduled_at DATETIME NULL AFTER service_id');
        $connection->exec('ALTER TABLE order_items ADD INDEX idx_order_items_service_schedule (service_id, scheduled_at)');
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE order_items DROP INDEX idx_order_items_service_schedule');
        $connection->exec('ALTER TABLE order_items DROP COLUMN scheduled_at');
        $connection->exec('ALTER TABLE cart_items DROP COLUMN scheduled_at');
    }
};
