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

        return $row ?: null;
    }

    public function listEnabled(): array
    {
        $stmt = $this->connection->query(
            'SELECT * FROM payment_methods WHERE is_enabled = 1 ORDER BY sort_order ASC'
        );

        return $stmt->fetchAll();
    }
}
