<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Infrastructure\Config\Config;
use PDO;
use PDOException;

/**
 * Wrapper de conexión PDO. Mantiene una única instancia por request (patrón
 * registro simple, no singleton global rígido, para facilitar testing/inyección futura).
 *
 * Si la base de datos configurada aún no existe, se crea automáticamente
 * (CREATE DATABASE IF NOT EXISTS) para simplificar el primer arranque local.
 */
final class Connection
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        $host = Config::get('database.host');
        $port = Config::get('database.port');
        $database = Config::get('database.database');
        $username = Config::get('database.username');
        $password = Config::get('database.password');
        $charset = Config::get('database.charset', 'utf8mb4');

        self::ensureDatabaseExists($host, $port, $database, $username, $password, $charset);

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // No se expone el mensaje original (podría incluir credenciales) más allá del log interno.
            throw new PDOException('No fue posible conectar a la base de datos: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Crea la base de datos si no existe todavía. Facilita el primer arranque
     * en un entorno local (XAMPP) sin pasos manuales adicionales.
     */
    private static function ensureDatabaseExists(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        string $charset
    ): void {
        $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
        $connection = new PDO($serverDsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $connection->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s_unicode_ci',
            $database,
            $charset,
            $charset
        ));
    }
}
