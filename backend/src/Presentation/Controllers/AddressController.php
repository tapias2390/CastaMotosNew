<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Address\CreateAddressUseCase;
use App\Application\UseCases\Address\DeleteAddressUseCase;
use App\Application\UseCases\Address\SetPrimaryAddressUseCase;
use App\Application\UseCases\Address\UpdateAddressUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoAddressRepository;

final class AddressController
{
    private PdoAddressRepository $addresses;

    private const RULES = [
        'recipient_name' => 'required|max:150',
        'phone' => 'required|max:30',
        'country' => 'required|max:100',
        'state' => 'required|max:100',
        'city' => 'required|max:100',
        'address_line' => 'required|max:255',
        'complement' => 'max:255',
        'postal_code' => 'max:20',
        'reference' => 'max:255',
    ];

    public function __construct()
    {
        $this->addresses = new PdoAddressRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        Response::success($this->addresses->listForUser($user->id));
    }

    public function store(Request $request): void
    {
        $data = Validator::make($request->input(), self::RULES)->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        $id = (new CreateAddressUseCase($this->addresses))->handle($user->id, $data);

        Response::success($this->addresses->find($id), 'Dirección creada correctamente.', 201);
    }

    public function update(Request $request, string $id): void
    {
        $data = Validator::make($request->input(), self::RULES)->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        (new UpdateAddressUseCase($this->addresses))->handle($user->id, (int) $id, $data);

        Response::success($this->addresses->find((int) $id), 'Dirección actualizada correctamente.');
    }

    public function destroy(Request $request, string $id): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        (new DeleteAddressUseCase($this->addresses))->handle($user->id, (int) $id);

        Response::success(null, 'Dirección eliminada correctamente.');
    }

    public function setPrimary(Request $request, string $id): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        (new SetPrimaryAddressUseCase($this->addresses))->handle($user->id, (int) $id);

        Response::success(null, 'Dirección marcada como principal.');
    }
}
