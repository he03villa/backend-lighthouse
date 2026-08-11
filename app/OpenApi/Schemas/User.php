<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
        new OA\Property(property: 'timezone', type: 'string', nullable: true),
        new OA\Property(property: 'locale', type: 'string', nullable: true),
        new OA\Property(property: 'is_super_admin', type: 'boolean', description: 'Indica si el usuario es super administrador.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'tenants', type: 'array', items: new OA\Items(ref: '#/components/schemas/TenantMembership'), description: 'Tenants del usuario con sus roles y permisos por tenant.'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'Unión de roles del usuario en todos sus tenants.'),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), description: 'Unión de permisos del usuario en todos sus tenants.'),
    ],
)]
class User {}
