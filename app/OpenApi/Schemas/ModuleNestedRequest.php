<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ModuleNestedRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Módulo 1'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'activities', type: 'array', items: new OA\Items(ref: '#/components/schemas/ActivityNestedRequest'), nullable: true),
    ],
)]
class ModuleNestedRequest {}
