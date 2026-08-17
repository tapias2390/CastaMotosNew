<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Coordenadas exactas del servicio (sección 12: "cómo llegar"). El campo
 * "location" (texto libre, desde la Fase 1) sigue siendo obligatorio para
 * mostrar la dirección legible; latitud/longitud son opcionales y, cuando
 * existen, hacen que el enlace "Cómo llegar" apunte al punto exacto en vez
 * de depender de que Google Maps interprete bien el texto de la dirección.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE services
                ADD COLUMN latitude DECIMAL(10, 7) NULL AFTER location,
                ADD COLUMN longitude DECIMAL(10, 7) NULL AFTER latitude'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('ALTER TABLE services DROP COLUMN latitude, DROP COLUMN longitude');
    }
};
