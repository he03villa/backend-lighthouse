<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Enrollment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'dropped']),
        new OA\Property(property: 'enrolled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'participant', ref: '#/components/schemas/Participant', description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'program', ref: '#/components/schemas/Program', description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'progress', ref: '#/components/schemas/Progress', description: 'Solo cuando se carga la relación.'),
    ],
)]
class Enrollment {}
