<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Tenant',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Club Deportivo'),
        new OA\Property(property: 'slug', type: 'string', example: 'club-deportivo-abc123'),
        new OA\Property(property: 'type', type: 'string', enum: ['individual', 'organization', 'family']),
        new OA\Property(property: 'config', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Solo cuando el tenant llega desde la relación del usuario.'),
    ],
)]
class Tenant {}
