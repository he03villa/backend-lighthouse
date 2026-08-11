<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreProgramRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Programa de Verano'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'age_group', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'duration_weeks', type: 'integer', minimum: 1, maximum: 260, nullable: true),
        new OA\Property(property: 'is_published', type: 'boolean', nullable: true),
        new OA\Property(property: 'thumbnail', type: 'string', maxLength: 2048, nullable: true),
        new OA\Property(property: 'config', nullable: true),
        new OA\Property(property: 'modules', type: 'array', items: new OA\Items(ref: '#/components/schemas/ModuleNestedRequest'), nullable: true),
    ],
)]
class StoreProgramRequest {}
