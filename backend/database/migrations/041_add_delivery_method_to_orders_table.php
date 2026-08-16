<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Paso 3 del checkout (sección 19): "Método de entrega". No existía columna
 * para esto en la tabla orders creada en la Fase 1.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "ALTER TABLE orders ADD COLUMN delivery_method VARCHAR(30) NOT NULL DEFAULT 'domicilio' AFTER store_id"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE orders DROP COLUMN delivery_method');
    }
};
