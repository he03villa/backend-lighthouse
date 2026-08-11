<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreEnrollmentRequest',
    type: 'object',
    required: ['participant_id', 'program_id'],
    properties: [
        new OA\Property(property: 'participant_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'program_id', type: 'string', format: 'uuid'),
    ],
)]
class StoreEnrollmentRequest {}
