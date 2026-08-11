<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Program',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Programa de Verano'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'age_group', type: 'string', nullable: true),
        new OA\Property(property: 'duration_weeks', type: 'integer', nullable: true),
        new OA\Property(property: 'is_published', type: 'boolean', example: false),
        new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
        new OA\Property(property: 'config', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'modules', type: 'array', items: new OA\Items(ref: '#/components/schemas/Module'), description: 'Solo cuando se carga la relación.'),
    ],
)]
class Program {}
