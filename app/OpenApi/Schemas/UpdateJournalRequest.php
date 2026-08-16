<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateJournalRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'participant_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'entry_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'visibility', type: 'string', enum: ['private', 'shared_family', 'shared_participant', 'public'], nullable: true),
    ],
)]
class UpdateJournalRequest {}
