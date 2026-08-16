<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Validation\Validator;
use App\Exceptions\ValidationException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoInventoryRepository;

/**
 * Gestión de inventario (sección 25, permiso manage-inventory).
 */
final class AdminInventoryController
{
    private PdoInventoryRepository $inventory;

    public function __construct()
    {
        $this->inventory = new PdoInventoryRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        Response::success($this->inventory->listWithProductInfo($request->query()));
    }

    public function adjust(Request $request, string $productId): void
    {
        $data = Validator::make($request->input(), [
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer',
            'reason' => 'required|max:255',
        ])->validate();

        $quantity = (int) $data['quantity'];

        // "in"/"out" son magnitudes (siempre positivas, el "type" da el signo);
        // "adjustment" es un delta con signo, pero no tiene sentido que sea 0.
        if (in_array($data['type'], ['in', 'out'], true) && $quantity <= 0) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'quantity' => ['Debe ser un número mayor a 0 para entradas/salidas.'],
            ]);
        }
        if ($data['type'] === 'adjustment' && $quantity === 0) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'quantity' => ['Un ajuste no puede ser 0.'],
            ]);
        }

        /** @var \App\Domain\Entities\User $user */
        $user = $request->attribute('auth_user');

        $this->inventory->adjust((int) $productId, $data['type'], (int) $data['quantity'], $data['reason'], $user->id);

        Response::success(null, 'Inventario actualizado.');
    }

    public function movements(Request $request): void
    {
        Response::success($this->inventory->movements($request->query()));
    }
}
