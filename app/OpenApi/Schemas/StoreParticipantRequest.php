<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreParticipantRequest',
    type: 'object',
    required: ['first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'first_name', type: 'string', maxLength: 255, example: 'Niño'),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 255, example: 'Participante'),
        new OA\Property(property: 'birth_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'avatar', type: 'string', maxLength: 2048, nullable: true),
        new OA\Property(property: 'metadata', nullable: true),
        new OA\Property(property: 'guardians', type: 'array', items: new OA\Items(ref: '#/components/schemas/GuardianRequest'), description: 'Los guardians se crean como usuarios y se agregan como miembros con rol parent.'),
    ],
)]
class StoreParticipantRequest {}
