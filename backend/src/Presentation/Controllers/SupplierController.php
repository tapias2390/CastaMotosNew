<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Validation\Validator;
use App\Exceptions\NotFoundException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoSupplierRepository;

/**
 * Directorio de proveedores (admin, sección nueva) — a diferencia de
 * BrandController, no es público: solo quien tiene manage-suppliers
 * necesita verlo (agenda interna, no aparece en el catálogo del cliente).
 */
final class SupplierController
{
    private PdoSupplierRepository $suppliers;

    private const RULES = [
        'name' => 'required|max:150',
        'contact_name' => 'max:150',
        'phone' => 'max:30',
        'email' => 'email|max:150',
        'tax_id' => 'max:50',
        'address' => 'max:255',
        'notes' => 'max:2000',
        'status' => 'in:active,inactive',
    ];

    public function __construct()
    {
        $this->suppliers = new PdoSupplierRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        $includeInactive = $request->query('include_inactive') === '1';

        Response::success($this->suppliers->list($includeInactive));
    }

    public function store(Request $request): void
    {
        $data = Validator::make($request->input(), self::RULES)->validate();

        $id = $this->suppliers->create($data);

        Response::success($this->suppliers->find($id), 'Proveedor creado correctamente.', 201);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->suppliers->exists((int) $id)) {
            throw new NotFoundException('Proveedor no encontrado.');
        }

        $data = Validator::make($request->input(), self::RULES)->validate();
        $this->suppliers->update((int) $id, $data);

        Response::success($this->suppliers->find((int) $id), 'Proveedor actualizado correctamente.');
    }

    public function destroy(Request $request, string $id): void
    {
        if (!$this->suppliers->exists((int) $id)) {
            throw new NotFoundException('Proveedor no encontrado.');
        }

        $this->suppliers->delete((int) $id);

        Response::success(null, 'Proveedor eliminado correctamente.');
    }
}
