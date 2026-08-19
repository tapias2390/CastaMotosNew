<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Vincula cada producto a su proveedor (opcional — ON DELETE SET NULL: borrar
 * un proveedor no debe borrar ni bloquear el producto, solo desvincularlo).
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE products
                ADD COLUMN supplier_id INT UNSIGNED NULL AFTER brand_id,
                ADD CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE SET NULL'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE products DROP FOREIGN KEY fk_products_supplier, DROP COLUMN supplier_id');
    }
};
