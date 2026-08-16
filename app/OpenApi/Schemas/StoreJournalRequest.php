<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreJournalRequest',
    type: 'object',
    required: ['content'],
    properties: [
        new OA\Property(property: 'participant_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'content', type: 'string', example: 'Sesión completada.'),
        new OA\Property(property: 'visibility', type: 'string', enum: ['private', 'shared_family', 'shared_participant', 'public'], nullable: true),
    ],
)]
class StoreJournalRequest {}
