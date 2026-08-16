<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Favorite\AddFavoriteUseCase;
use App\Application\UseCases\Favorite\RemoveFavoriteUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Exceptions\ValidationException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoFavoriteRepository;
use App\Infrastructure\Persistence\PdoProductRepository;
use App\Infrastructure\Persistence\PdoServiceRepository;

final class FavoriteController
{
    private PdoFavoriteRepository $favorites;
    private PdoProductRepository $products;
    private PdoServiceRepository $services;

    public function __construct()
    {
        $connection = Connection::get();
        $this->favorites = new PdoFavoriteRepository($connection);
        $this->products = new PdoProductRepository($connection);
        $this->services = new PdoServiceRepository($connection);
    }

    public function index(Request $request): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        Response::success($this->favorites->listForUser($user->id));
    }

    public function store(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'type' => 'required|in:product,service',
            'id' => 'required|integer',
        ])->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        (new AddFavoriteUseCase($this->favorites, $this->products, $this->services))
            ->handle($user->id, $data['type'], (int) $data['id']);

        Response::success(null, 'Agregado a favoritos.', 201);
    }

    public function destroy(Request $request, string $type, string $id): void
    {
        $this->assertValidType($type);

        /** @var User $user */
        $user = $request->attribute('auth_user');

        (new RemoveFavoriteUseCase($this->favorites))->handle($user->id, $type, (int) $id);

        Response::success(null, 'Eliminado de favoritos.');
    }

    public function check(Request $request): void
    {
        $type = (string) $request->query('type', '');
        $id = (int) $request->query('id', 0);
        $this->assertValidType($type);

        /** @var User $user */
        $user = $request->attribute('auth_user');

        Response::success(['is_favorite' => $this->favorites->isFavorite($user->id, $type, $id)]);
    }

    private function assertValidType(string $type): void
    {
        if (!in_array($type, ['product', 'service'], true)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'type' => ['Debe ser "product" o "service".'],
            ]);
        }
    }
}
