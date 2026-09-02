<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreForumCommentRequest',
    type: 'object',
    required: ['content'],
    properties: [
        new OA\Property(property: 'content', type: 'string', example: 'Muy util, gracias por compartir.'),
    ],
)]
class StoreForumCommentRequest {}
