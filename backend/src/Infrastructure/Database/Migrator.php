<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

/**
 * Motor de migraciones minimalista (sin dependencias externas).
 *
 * Cada archivo en /database/migrations debe devolver una instancia de Migration:
 *   return new class extends Migration { public function up(PDO $c): void {...} ... };
 *
 * Se registra cada migración aplicada en la tabla `migrations` junto con un
 * número de "batch", para poder revertir el último lote con rollback().
 */
final class Migrator
{
    private PDO $connection;
    private string $migrationsPath;

    public function __construct(PDO $connection, string $migrationsPath)
    {
        $this->connection = $connection;
        $this->migrationsPath = rtrim($migrationsPath, '/\\');
        $this->ensureMigrationsTable();
    }

    /**
     * Ejecuta todas las migraciones pendientes (no aplicadas todavía).
     *
     * @return string[] Nombres de los archivos aplicados en esta corrida.
     */
    public function migrate(): array
    {
        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles();
        $pending = array_values(array_diff($files, $applied));

        if (empty($pending)) {
            return [];
        }

        $batch = $this->getNextBatchNumber();
        $ran = [];

        foreach ($pending as $file) {
            /** @var Migration $migration */
            $migration = require $this->migrationsPath . '/' . $file;
            $migration->up($this->connection);

            $stmt = $this->connection->prepare(
                'INSERT INTO migrations (migration, batch, executed_at) VALUES (:migration, :batch, NOW())'
            );
            $stmt->execute(['migration' => $file, 'batch' => $batch]);

            $ran[] = $file;
        }

        return $ran;
    }

    /**
     * Revierte todas las migraciones del último batch aplicado.
     *
     * @return string[] Nombres de los archivos revertidos.
     */
    public function rollback(): array
    {
        $lastBatch = $this->getLastBatchNumber();
        if ($lastBatch === null) {
            return [];
        }

        $stmt = $this->connection->prepare('SELECT migration FROM migrations WHERE batch = :batch ORDER BY id DESC');
        $stmt->execute(['batch' => $lastBatch]);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($files as $file) {
            /** @var Migration $migration */
            $migration = require $this->migrationsPath . '/' . $file;
            $migration->down($this->connection);

            $delete = $this->connection->prepare('DELETE FROM migrations WHERE migration = :migration');
            $delete->execute(['migration' => $file]);
        }

        return $files;
    }

    private function ensureMigrationsTable(): void
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                executed_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function getAppliedMigrations(): array
    {
        $stmt = $this->connection->query('SELECT migration FROM migrations');

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        $files = array_map('basename', $files);
        sort($files);

        return $files;
    }

    private function getNextBatchNumber(): int
    {
        return ($this->getLastBatchNumber() ?? 0) + 1;
    }

    private function getLastBatchNumber(): ?int
    {
        $stmt = $this->connection->query('SELECT MAX(batch) FROM migrations');
        $value = $stmt->fetchColumn();

        return $value !== null ? (int) $value : null;
    }
}
