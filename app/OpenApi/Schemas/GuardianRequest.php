<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GuardianRequest',
    type: 'object',
    required: ['email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, nullable: true, example: 'Madre de Niño'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'madre@example.com'),
        new OA\Property(property: 'relationship', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'is_primary', type: 'boolean', nullable: true, example: true),
        new OA\Property(property: 'permissions', nullable: true),
    ],
)]
class GuardianRequest {}
