<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Activity',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Reflexión inicial'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['upload', 'reflection', 'completion', 'quiz']),
        new OA\Property(property: 'order', type: 'integer', example: 0),
        new OA\Property(property: 'config', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class Activity {}
