<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Validation\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoPaymentMethodRepository;

/**
 * Configuración administrativa de métodos de pago (sección 21, "MUY
 * IMPORTANTE": activar/desactivar sin tocar código, permiso
 * manage-payment-methods). El checkout público solo ve lo que
 * PaymentMethodController::index() expone (sin "config").
 */
final class AdminPaymentMethodController
{
    private PdoPaymentMethodRepository $paymentMethods;

    public function __construct()
    {
        $this->paymentMethods = new PdoPaymentMethodRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        Response::success($this->paymentMethods->listAll());
    }

    public function update(Request $request, string $id): void
    {
        if ($this->paymentMethods->find((int) $id) === null) {
            throw new NotFoundException('Método de pago no encontrado.');
        }

        $data = Validator::make($request->input(), [
            'is_enabled' => 'required|boolean',
        ])->validate();

        $config = $request->input('config');
        if ($config !== null && !is_array($config)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'config' => ['Debe ser un objeto JSON válido.'],
            ]);
        }

        $this->paymentMethods->updateConfig((int) $id, (bool) $data['is_enabled'], $config);

        Response::success($this->paymentMethods->find((int) $id), 'Método de pago actualizado correctamente.');
    }
}
