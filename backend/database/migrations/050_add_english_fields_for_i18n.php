<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Selector de idioma ES/EN (sección nueva): campos EN opcionales para el
 * contenido que el admin escribe a mano (productos, servicios, categorías).
 * NULL/vacío = se muestra en español igual (fallback, ver ProductController/
 * ServiceController/CategoryController) — nunca rompe nada si el admin no
 * llegó a traducir un ítem todavía. Nombres de marca (brands) no se tocan:
 * son nombres propios (AKT, Bajaj), no se traducen.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE products
                ADD COLUMN name_en VARCHAR(200) NULL AFTER name,
                ADD COLUMN short_description_en VARCHAR(500) NULL AFTER short_description,
                ADD COLUMN description_en TEXT NULL AFTER description'
        );
        $connection->exec(
            'ALTER TABLE services
                ADD COLUMN name_en VARCHAR(200) NULL AFTER name,
                ADD COLUMN description_en TEXT NULL AFTER description'
        );
        $connection->exec(
            'ALTER TABLE categories ADD COLUMN name_en VARCHAR(100) NULL AFTER name'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE categories DROP COLUMN name_en');
        $connection->exec('ALTER TABLE services DROP COLUMN name_en, DROP COLUMN description_en');
        $connection->exec('ALTER TABLE products DROP COLUMN name_en, DROP COLUMN short_description_en, DROP COLUMN description_en');
    }
};
