<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

/**
 * Clase base para los seeders (datos iniciales/semilla).
 */
abstract class Seeder
{
    abstract public function run(PDO $connection): void;
}
