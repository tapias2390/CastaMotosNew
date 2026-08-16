<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Registro de la comunicación con pasarelas externas (Wompi, MercadoPago, etc.).
 * Nunca almacena datos sensibles de tarjeta (sección 20/54): solo IDs y respuestas del proveedor.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE payment_transactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                payment_id INT UNSIGNED NOT NULL,
                provider VARCHAR(50) NOT NULL,
                transaction_id VARCHAR(150) NULL,
                raw_response JSON NULL,
                status VARCHAR(30) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payment_transactions_payment (payment_id),
                CONSTRAINT fk_payment_transactions_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS payment_transactions');
    }
};
