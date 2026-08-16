<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface LoginHistoryRepositoryInterface
{
    public function record(?int $userId, ?string $emailAttempted, string $ipAddress, string $userAgent, string $status): void;
}
