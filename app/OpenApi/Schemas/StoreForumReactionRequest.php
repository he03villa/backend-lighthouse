<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreForumReactionRequest',
    type: 'object',
    required: ['type'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['like', 'heart', 'helpful']),
    ],
)]
class StoreForumReactionRequest {}
