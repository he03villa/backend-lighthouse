<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreMessageRequest',
    type: 'object',
    required: ['content'],
    properties: [
        new OA\Property(property: 'content', type: 'string', example: 'Hola, como va el progreso?'),
        new OA\Property(property: 'media', type: 'array', nullable: true, items: new OA\Items(
            type: 'object',
            properties: [
                new OA\Property(property: 'url', type: 'string', format: 'uri'),
                new OA\Property(property: 'type', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ],
        )),
    ],
)]
class StoreMessageRequest {}
