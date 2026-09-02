<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateFieldNoteRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'content', type: 'string', example: 'Buena participacion.'),
        new OA\Property(property: 'visibility', type: 'string', enum: ['private', 'shared_family', 'shared_participant', 'public'], nullable: true),
        new OA\Property(property: 'session_date', type: 'string', format: 'date', nullable: true),
    ],
)]
class UpdateFieldNoteRequest {}
