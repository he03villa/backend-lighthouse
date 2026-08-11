<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TenantMembership',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Mi Club'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'type', type: 'string', example: 'organization'),
        new OA\Property(property: 'config', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'Roles del usuario en este tenant.'),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), description: 'Permisos del usuario en este tenant.'),
    ],
)]
class TenantMembership {}
