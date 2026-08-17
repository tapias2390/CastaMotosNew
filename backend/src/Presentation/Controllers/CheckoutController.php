<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Checkout\CheckoutUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Exceptions\NotFoundException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoAddressRepository;
use App\Infrastructure\Persistence\PdoCartRepository;
use App\Infrastructure\Persistence\PdoCouponRepository;
use App\Infrastructure\Persistence\PdoOrderRepository;
use App\Infrastructure\Persistence\PdoPaymentMethodRepository;
use App\Infrastructure\Persistence\PdoPushSubscriptionRepository;

final class CheckoutController
{
    private PdoCartRepository $carts;
    private PdoOrderRepository $orders;
    private PdoAddressRepository $addresses;
    private PdoPaymentMethodRepository $paymentMethods;
    private PdoPushSubscriptionRepository $pushSubscriptions;
    private PdoCouponRepository $coupons;

    public function __construct()
    {
        $connection = Connection::get();
        $this->carts = new PdoCartRepository($connection);
        $this->orders = new PdoOrderRepository($connection);
        $this->addresses = new PdoAddressRepository($connection);
        $this->paymentMethods = new PdoPaymentMethodRepository($connection);
        $this->pushSubscriptions = new PdoPushSubscriptionRepository($connection);
        $this->coupons = new PdoCouponRepository($connection);
    }

    public function store(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'address_id' => 'required|integer',
            'payment_method_id' => 'required|integer',
            'delivery_method' => 'required|in:domicilio,recogida_tienda',
            'notes' => 'max:500',
        ])->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        $cart = $this->carts->resolveActiveCart($user->id, null);

        $useCase = new CheckoutUseCase($this->carts, $this->orders, $this->addresses, $this->paymentMethods, $this->pushSubscriptions, $this->coupons);
        $result = $useCase->handle(
            $user->id,
            (int) $cart['id'],
            (int) $data['address_id'],
            (int) $data['payment_method_id'],
            $data['delivery_method'],
            $data['notes'] ?? null,
            trim($user->name . ' ' . $user->lastName),
            $user->email
        );

        $order = $this->orders->findByOrderNumberForUser($result['order_number'], $user->id);

        Response::success($order, 'Pedido creado correctamente.', 201);
    }

    public function show(Request $request, string $orderNumber): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        $order = $this->orders->findByOrderNumberForUser($orderNumber, $user->id);
        if ($order === null) {
            throw new NotFoundException('Pedido no encontrado.');
        }

        Response::success($order);
    }
}
