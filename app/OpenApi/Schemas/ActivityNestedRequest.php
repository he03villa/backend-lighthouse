<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ActivityNestedRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Reflexión inicial'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['upload', 'reflection', 'completion', 'quiz'], nullable: true),
        new OA\Property(property: 'order', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'config', nullable: true),
    ],
)]
class ActivityNestedRequest {}
