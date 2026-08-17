<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Support\CartPricingCalculator;
use App\Application\UseCases\Cart\AddCartItemUseCase;
use App\Application\UseCases\Cart\RemoveCartItemUseCase;
use App\Application\UseCases\Cart\UpdateCartItemUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Exceptions\ValidationException;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoCartRepository;
use App\Infrastructure\Persistence\PdoProductRepository;
use App\Infrastructure\Persistence\PdoServiceRepository;

final class CartController
{
    private PdoCartRepository $carts;
    private PdoProductRepository $products;
    private PdoServiceRepository $services;

    public function __construct()
    {
        $connection = Connection::get();
        $this->carts = new PdoCartRepository($connection);
        $this->products = new PdoProductRepository($connection);
        $this->services = new PdoServiceRepository($connection);
    }

    public function show(Request $request): void
    {
        $cart = $this->resolveCart($request);

        Response::success($this->buildCartResponse($cart));
    }

    public function addItem(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'product_id' => 'integer',
            'service_id' => 'integer',
            'quantity' => 'required|integer|gte:1',
        ])->validate();

        [$productId, $serviceId] = $this->assertExactlyOneReference($data);

        $cart = $this->resolveCart($request);

        (new AddCartItemUseCase($this->carts, $this->products, $this->services))
            ->handle((int) $cart['id'], $productId, $serviceId, (int) $data['quantity'], $data['scheduled_at'] ?? null);

        Response::success($this->buildCartResponse($cart), 'Producto agregado al carrito.', 201);
    }

    public function updateItem(Request $request, string $itemId): void
    {
        $data = Validator::make($request->input(), ['quantity' => 'required|integer|gte:1'])->validate();

        $cart = $this->resolveCart($request);

        (new UpdateCartItemUseCase($this->carts, $this->products))
            ->handle((int) $cart['id'], (int) $itemId, (int) $data['quantity']);

        Response::success($this->buildCartResponse($cart), 'Cantidad actualizada.');
    }

    public function removeItem(Request $request, string $itemId): void
    {
        $cart = $this->resolveCart($request);

        (new RemoveCartItemUseCase($this->carts))->handle((int) $cart['id'], (int) $itemId);

        Response::success($this->buildCartResponse($cart), 'Producto eliminado del carrito.');
    }

    public function clear(Request $request): void
    {
        $cart = $this->resolveCart($request);

        $this->carts->clear((int) $cart['id']);

        Response::success($this->buildCartResponse($cart), 'Carrito vaciado.');
    }

    /**
     * Resuelve el carrito activo por usuario autenticado o por X-Cart-Token
     * de invitado (sección 18). No requiere AuthMiddleware: el carrito
     * funciona para visitantes sin cuenta.
     */
    private function resolveCart(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->attribute('auth_user');
        $token = (string) $request->header('X-Cart-Token', '');

        return $this->carts->resolveActiveCart(
            $user?->id,
            $token !== '' ? $token : null
        );
    }

    private function buildCartResponse(array $cart): array
    {
        $items = $this->carts->itemsWithLiveData((int) $cart['id']);

        $pricing = CartPricingCalculator::calculate(
            $items,
            'domicilio', // vista previa; el envío real se confirma en el checkout con el método elegido
            (float) Config::get('app.shipping.flat_rate', 12000),
            (float) Config::get('app.shipping.free_threshold', 300000)
        );

        return array_merge([
            'cart_id' => (int) $cart['id'],
            'cart_token' => $cart['token'],
            'items' => $items,
        ], $pricing);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function assertExactlyOneReference(array $data): array
    {
        $productId = isset($data['product_id']) ? (int) $data['product_id'] : null;
        $serviceId = isset($data['service_id']) ? (int) $data['service_id'] : null;

        if (($productId === null) === ($serviceId === null)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'product_id' => ['Debes indicar exactamente uno: "product_id" o "service_id".'],
            ]);
        }

        return [$productId, $serviceId];
    }
}
