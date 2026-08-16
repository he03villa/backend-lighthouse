<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PlanningTask',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'column_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string', example: 'Preparar sesión 3'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'position', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class PlanningTask {}
