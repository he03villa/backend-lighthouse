<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AddGroupMemberRequest',
    type: 'object',
    required: ['participant_id'],
    properties: [
        new OA\Property(property: 'participant_id', type: 'string', format: 'uuid'),
    ],
)]
class AddGroupMemberRequest {}
