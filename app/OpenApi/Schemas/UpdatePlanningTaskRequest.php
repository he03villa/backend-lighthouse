<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdatePlanningTaskRequest',
    type: 'object',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'position', type: 'integer', nullable: true),
    ],
)]
class UpdatePlanningTaskRequest {}
