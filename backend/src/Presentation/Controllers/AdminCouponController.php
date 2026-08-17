<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Validation\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoCouponRepository;

/**
 * CRUD de cupones (sección 30, permiso manage-coupons ya existente).
 */
final class AdminCouponController
{
    private PdoCouponRepository $coupons;

    private const RULES = [
        'code' => 'required|max:50',
        'type' => 'required|in:percentage,fixed',
        'value' => 'required|numeric|gte:0',
        'min_purchase' => 'numeric|gte:0',
        'usage_limit' => 'integer|gte:1',
        'status' => 'in:active,inactive',
    ];

    public function __construct()
    {
        $this->coupons = new PdoCouponRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        Response::success($this->coupons->paginateForAdmin($request->query()));
    }

    public function store(Request $request): void
    {
        $data = Validator::make($request->input(), self::RULES)->validate();
        $this->assertDatesAreCoherent($data);

        if ($this->coupons->existsByCode($data['code'])) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'code' => ['Ya existe un cupón con este código.'],
            ]);
        }

        $id = $this->coupons->create($data);

        Response::success($this->coupons->find($id), 'Cupón creado correctamente.', 201);
    }

    public function update(Request $request, string $id): void
    {
        if ($this->coupons->find((int) $id) === null) {
            throw new NotFoundException('Cupón no encontrado.');
        }

        $data = Validator::make($request->input(), self::RULES)->validate();
        $this->assertDatesAreCoherent($data);

        if ($this->coupons->existsByCode($data['code'], (int) $id)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'code' => ['Ya existe un cupón con este código.'],
            ]);
        }

        $this->coupons->update((int) $id, $data);

        Response::success($this->coupons->find((int) $id), 'Cupón actualizado correctamente.');
    }

    public function destroy(Request $request, string $id): void
    {
        if ($this->coupons->find((int) $id) === null) {
            throw new NotFoundException('Cupón no encontrado.');
        }

        $this->coupons->delete((int) $id);

        Response::success(null, 'Cupón eliminado correctamente.');
    }

    private function assertDatesAreCoherent(array $data): void
    {
        $starts = $data['starts_at'] ?? null;
        $ends = $data['ends_at'] ?? null;

        if ($starts && $ends && strtotime($starts) > strtotime($ends)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'ends_at' => ['La fecha de finalización debe ser posterior a la de inicio.'],
            ]);
        }

        if ($data['type'] === 'percentage' && (float) $data['value'] > 100) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'value' => ['Un descuento porcentual no puede superar 100.'],
            ]);
        }
    }
}
