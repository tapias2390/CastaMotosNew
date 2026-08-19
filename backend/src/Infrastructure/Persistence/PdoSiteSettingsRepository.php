<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

/** Configuración general del sitio en clave/valor (migración 047). */
final class PdoSiteSettingsRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function get(string $key): ?string
    {
        $stmt = $this->connection->prepare('SELECT value FROM site_settings WHERE setting_key = :key');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : null;
    }
}
