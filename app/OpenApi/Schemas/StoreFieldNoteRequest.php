<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreFieldNoteRequest',
    type: 'object',
    required: ['participant_id', 'content'],
    properties: [
        new OA\Property(property: 'participant_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'activity_submission_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'session_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'content', type: 'string', example: 'Buena participación.'),
        new OA\Property(property: 'visibility', type: 'string', enum: ['private', 'shared_family', 'shared_participant', 'public'], nullable: true),
    ],
)]
class StoreFieldNoteRequest {}
