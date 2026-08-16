<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PlanningColumn',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'board_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Backlog'),
        new OA\Property(property: 'position', type: 'integer'),
        new OA\Property(property: 'tasks_count', type: 'integer', nullable: true),
        new OA\Property(property: 'tasks', type: 'array', items: new OA\Items(ref: '#/components/schemas/PlanningTask'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class PlanningColumn {}
