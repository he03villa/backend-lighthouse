<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MovePlanningTaskRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'column_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'position', type: 'integer', nullable: true),
    ],
)]
class MovePlanningTaskRequest {}
