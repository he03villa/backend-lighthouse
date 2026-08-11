<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Module',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Módulo 1'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'activities', type: 'array', items: new OA\Items(ref: '#/components/schemas/Activity'), description: 'Solo cuando se carga la relación.'),
    ],
)]
class Module {}
