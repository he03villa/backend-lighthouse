<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Evidence',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', type: 'string', enum: ['text', 'image', 'video', 'audio', 'file']),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'metadata', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class Evidence {}
