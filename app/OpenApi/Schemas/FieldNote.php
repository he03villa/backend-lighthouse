<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FieldNote',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'content', type: 'string', example: 'La participante avanzó bien en la actividad.'),
        new OA\Property(property: 'visibility', type: 'string', enum: ['private', 'shared_family', 'shared_participant', 'public']),
        new OA\Property(property: 'session_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'author', ref: '#/components/schemas/User', description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'participant', ref: '#/components/schemas/Participant', description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'submission', ref: '#/components/schemas/ActivitySubmission', description: 'Solo cuando se carga la relación.'),
    ],
)]
class FieldNote {}
