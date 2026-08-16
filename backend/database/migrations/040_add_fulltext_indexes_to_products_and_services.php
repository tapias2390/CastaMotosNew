<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Índices FULLTEXT para búsqueda por relevancia (sección 14: "Búsqueda por
 * relevancia"). MariaDB 10.4 soporta FULLTEXT sobre tablas InnoDB.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, short_description, description)'
        );
        $connection->exec(
            'ALTER TABLE services ADD FULLTEXT INDEX ft_services_search (name, description)'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE products DROP INDEX ft_products_search');
        $connection->exec('ALTER TABLE services DROP INDEX ft_services_search');
    }
};
