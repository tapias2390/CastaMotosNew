<?php

declare(strict_types=1);

use App\Infrastructure\Database\Seeder;

return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $roles = [
            ['cliente', 'Compra productos y contrata servicios en la plataforma.'],
            ['vendedor', 'Administra su propia tienda, productos y servicios.'],
            ['administrador', 'Gestiona la operación general de la plataforma.'],
            ['superadministrador', 'Control total, incluyendo configuración crítica del sistema.'],
        ];

        $stmt = $connection->prepare(
            'INSERT INTO roles (name, description) VALUES (:name, :description)
             ON DUPLICATE KEY UPDATE description = VALUES(description)'
        );

        foreach ($roles as [$name, $description]) {
            $stmt->execute(['name' => $name, 'description' => $description]);
        }
    }
};
