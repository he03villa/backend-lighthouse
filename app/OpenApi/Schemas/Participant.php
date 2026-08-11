<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Participant',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Niño'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Participante'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Niño Participante'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'avatar', type: 'string', nullable: true),
        new OA\Property(property: 'metadata', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'guardians', type: 'array', items: new OA\Items(ref: '#/components/schemas/Guardian'), description: 'Solo cuando se carga la relación.'),
        new OA\Property(property: 'groups', type: 'array', items: new OA\Items(ref: '#/components/schemas/Group'), description: 'Solo cuando se carga la relación.'),
    ],
)]
class Participant {}
