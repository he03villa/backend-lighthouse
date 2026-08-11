<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ActivitySubmission',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'submitted', 'reviewed', 'approved', 'rejected']),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'reviewed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'activity', ref: '#/components/schemas/Activity', description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'enrollment', ref: '#/components/schemas/Enrollment', description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'evidences', type: 'array', items: new OA\Items(ref: '#/components/schemas/Evidence'), description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'reviewed_by', ref: '#/components/schemas/User', description: 'Solo cuando se carga la relación.'),
    ],
)]
class ActivitySubmission {}
