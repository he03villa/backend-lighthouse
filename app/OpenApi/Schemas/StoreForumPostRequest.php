<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreForumPostRequest',
    type: 'object',
    required: ['title', 'content'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Consejos para lectura en casa'),
        new OA\Property(property: 'content', type: 'string', example: 'Comparto algunas estrategias que me han funcionado...'),
        new OA\Property(property: 'category', type: 'string', nullable: true, example: 'tips'),
    ],
)]
class StoreForumPostRequest {}
