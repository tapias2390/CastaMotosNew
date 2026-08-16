<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

/**
 * Clase base para las migraciones. Cada migración concreta implementa
 * up() (crear/alterar) y down() (revertir).
 */
abstract class Migration
{
    abstract public function up(PDO $connection): void;

    abstract public function down(PDO $connection): void;
}
