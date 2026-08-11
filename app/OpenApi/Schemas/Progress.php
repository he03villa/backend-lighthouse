<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Progress',
    type: 'object',
    properties: [
        new OA\Property(property: 'enrollment_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'percentage', type: 'number', format: 'float', example: 50.0),
        new OA\Property(property: 'completed_activities', type: 'integer', example: 1),
        new OA\Property(property: 'total_activities', type: 'integer', example: 2),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class Progress {}
