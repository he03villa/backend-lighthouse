<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreConversationRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'participant_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
        new OA\Property(property: 'title', type: 'string', nullable: true, example: 'Consulta sobre progreso'),
    ],
)]
class StoreConversationRequest {}
