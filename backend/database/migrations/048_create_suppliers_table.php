<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Directorio de proveedores del admin (agenda de a quién comprarle) + link a
 * productos (migración 049: products.supplier_id). Sin slug/página pública
 * a propósito — a diferencia de brands, un proveedor no se muestra al cliente.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE suppliers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                contact_name VARCHAR(150) NULL,
                phone VARCHAR(30) NULL,
                email VARCHAR(150) NULL,
                tax_id VARCHAR(50) NULL,
                address VARCHAR(255) NULL,
                notes TEXT NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS suppliers');
    }
};
