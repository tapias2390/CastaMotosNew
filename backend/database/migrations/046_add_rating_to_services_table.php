<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Reseñas (sección 26) son polimórficas desde la Fase 1 (reviewable_type
 * incluye 'service'), pero products fue la única tabla que llegó con
 * rating_avg/rating_count — a un servicio nunca se le pudo guardar su
 * promedio. Se agrega acá, mismo tipo/default que en products (migración
 * 010), para que ReviewRepository::recalculateRating() funcione igual para
 * los dos tipos sin un caso especial.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE services
                ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER status,
                ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_avg'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE services DROP COLUMN rating_avg, DROP COLUMN rating_count');
    }
};
