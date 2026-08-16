<?php

declare(strict_types=1);

namespace App\Application\UseCases\Address;

use App\Domain\Repositories\AddressRepositoryInterface;
use App\Exceptions\NotFoundException;

final class DeleteAddressUseCase
{
    public function __construct(private AddressRepositoryInterface $addresses)
    {
    }

    public function handle(int $userId, int $addressId): void
    {
        if (!$this->addresses->belongsToUser($addressId, $userId)) {
            throw new NotFoundException('Dirección no encontrada.');
        }

        $this->addresses->delete($addressId);
    }
}
