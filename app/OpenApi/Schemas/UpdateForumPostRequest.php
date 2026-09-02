<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateForumPostRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Titulo actualizado'),
        new OA\Property(property: 'content', type: 'string', example: 'Contenido actualizado'),
        new OA\Property(property: 'category', type: 'string', nullable: true),
        new OA\Property(property: 'pinned', type: 'boolean'),
    ],
)]
class UpdateForumPostRequest {}
