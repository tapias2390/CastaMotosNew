<?php

declare(strict_types=1);

namespace App\Application\UseCases\Address;

use App\Domain\Repositories\AddressRepositoryInterface;

final class CreateAddressUseCase
{
    public function __construct(private AddressRepositoryInterface $addresses)
    {
    }

    public function handle(int $userId, array $data): int
    {
        return $this->addresses->create($userId, $data);
    }
}
