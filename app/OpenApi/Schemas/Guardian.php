<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Guardian',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Madre de Niño'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'madre@example.com'),
        new OA\Property(property: 'relationship', type: 'string', nullable: true),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
        new OA\Property(property: 'permissions', nullable: true),
    ],
)]
class Guardian {}
