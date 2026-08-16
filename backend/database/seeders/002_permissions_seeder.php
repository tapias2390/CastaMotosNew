<?php

declare(strict_types=1);

use App\Infrastructure\Database\Seeder;

/**
 * Set base de permisos + asignación a roles vía role_permission.
 * Se amplía en fases posteriores a medida que se agregan módulos.
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $permissions = [
            'manage-users', 'manage-roles', 'manage-stores',
            'manage-products', 'manage-services', 'manage-categories', 'manage-brands',
            'manage-orders', 'manage-payments', 'manage-payment-methods',
            'manage-inventory', 'manage-reviews', 'manage-coupons', 'manage-promotions',
            'manage-settings', 'manage-notifications', 'manage-ai',
            'view-own-orders', 'view-own-store',
        ];

        $insert = $connection->prepare(
            'INSERT INTO permissions (name) VALUES (:name) ON DUPLICATE KEY UPDATE name = VALUES(name)'
        );
        foreach ($permissions as $permission) {
            $insert->execute(['name' => $permission]);
        }

        $rolePermissions = [
            'superadministrador' => $permissions, // todos
            'administrador' => [
                'manage-users', 'manage-stores', 'manage-products', 'manage-services',
                'manage-categories', 'manage-brands', 'manage-orders', 'manage-payments',
                'manage-payment-methods', 'manage-inventory', 'manage-reviews',
                'manage-coupons', 'manage-promotions', 'manage-notifications',
            ],
            'vendedor' => [
                'manage-products', 'manage-services', 'manage-inventory', 'view-own-store', 'view-own-orders',
            ],
            'cliente' => [
                'view-own-orders',
            ],
        ];

        $link = $connection->prepare(
            'INSERT INTO role_permission (role_id, permission_id)
             SELECT r.id, p.id FROM roles r, permissions p
             WHERE r.name = :role AND p.name = :permission
             ON DUPLICATE KEY UPDATE role_id = role_id'
        );

        foreach ($rolePermissions as $role => $perms) {
            foreach ($perms as $permission) {
                $link->execute(['role' => $role, 'permission' => $permission]);
            }
        }
    }
};
