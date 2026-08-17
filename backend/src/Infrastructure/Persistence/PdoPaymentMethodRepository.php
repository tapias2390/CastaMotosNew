<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\PaymentMethodRepositoryInterface;
use PDO;

final class PdoPaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM payment_methods WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $this->decodeConfig($row) : null;
    }

    public function listEnabled(): array
    {
        // Nunca "SELECT *" acá: esta lista la consume el checkout público
        // (sin autenticar) y "config" puede llegar a tener llaves de una
        // pasarela (sección 20) — jamás deben viajar al navegador.
        $stmt = $this->connection->query(
            "SELECT id, code, name, description, sort_order
             FROM payment_methods WHERE is_enabled = 1 ORDER BY sort_order ASC"
        );

        return $stmt->fetchAll();
    }

    public function listAll(): array
    {
        // Este sí incluye "config" a propósito: solo lo consume el panel
        // admin (permiso manage-payment-methods) para poder editarlo.
        $stmt = $this->connection->query('SELECT * FROM payment_methods ORDER BY sort_order ASC');

        return array_map([$this, 'decodeConfig'], $stmt->fetchAll());
    }

    public function updateConfig(int $id, bool $isEnabled, ?array $config): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE payment_methods SET is_enabled = :is_enabled, config = :config WHERE id = :id'
        );
        $stmt->execute([
            'is_enabled' => $isEnabled ? 1 : 0,
            'config' => $config !== null ? json_encode($config) : null,
            'id' => $id,
        ]);
    }

    private function decodeConfig(array $row): array
    {
        $row['config'] = $row['config'] !== null ? json_decode((string) $row['config'], true) : null;

        return $row;
    }
}
