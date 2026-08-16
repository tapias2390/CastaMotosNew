<?php

declare(strict_types=1);

namespace App\Application\UseCases\Address;

use App\Domain\Repositories\AddressRepositoryInterface;
use App\Exceptions\NotFoundException;

final class UpdateAddressUseCase
{
    public function __construct(private AddressRepositoryInterface $addresses)
    {
    }

    public function handle(int $userId, int $addressId, array $data): void
    {
        // Se verifica pertenencia para evitar IDOR (sección 6): un usuario no
        // debe poder editar direcciones de otro usuario adivinando el id.
        if (!$this->addresses->belongsToUser($addressId, $userId)) {
            throw new NotFoundException('Dirección no encontrada.');
        }

        $this->addresses->update($addressId, $data);
    }
}
