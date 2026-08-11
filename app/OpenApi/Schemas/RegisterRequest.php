<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    type: 'object',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Juan Pérez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'juan@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
        new OA\Property(property: 'tenant_name', type: 'string', maxLength: 255, nullable: true, description: 'Si se envía, crea el tenant y al usuario como owner.'),
        new OA\Property(property: 'tenant_type', type: 'string', enum: ['individual', 'organization', 'family'], nullable: true),
    ],
)]
class RegisterRequest {}
