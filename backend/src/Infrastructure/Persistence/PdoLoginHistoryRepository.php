<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\LoginHistoryRepositoryInterface;
use PDO;

final class PdoLoginHistoryRepository implements LoginHistoryRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function record(?int $userId, ?string $emailAttempted, string $ipAddress, string $userAgent, string $status): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO login_history (user_id, email_attempted, ip_address, user_agent, status)
             VALUES (:user_id, :email_attempted, :ip_address, :user_agent, :status)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'email_attempted' => $emailAttempted,
            'ip_address' => mb_substr($ipAddress, 0, 45),
            'user_agent' => mb_substr($userAgent, 0, 255),
            'status' => $status,
        ]);
    }
}
