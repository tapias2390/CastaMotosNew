<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Sección 30: cupones/descuentos ya tenían su tabla (coupons, Fase 1) pero
 * cero lógica encima. Se agrega la relación con el carrito (cupón aplicado
 * ahora mismo, se puede quitar) y con el pedido (snapshot de cuál se usó —
 * "coupon_code" queda aunque el cupón se edite/borre después, igual que
 * name_snapshot en order_items).
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE carts
                ADD COLUMN coupon_id INT UNSIGNED NULL AFTER status,
                ADD CONSTRAINT fk_carts_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE SET NULL'
        );
        $connection->exec(
            'ALTER TABLE orders
                ADD COLUMN coupon_id INT UNSIGNED NULL AFTER discount_total,
                ADD COLUMN coupon_code VARCHAR(50) NULL AFTER coupon_id,
                ADD CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE SET NULL'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE orders DROP FOREIGN KEY fk_orders_coupon, DROP COLUMN coupon_id, DROP COLUMN coupon_code');
        $connection->exec('ALTER TABLE carts DROP FOREIGN KEY fk_carts_coupon, DROP COLUMN coupon_id');
    }
};
