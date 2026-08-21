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
        new OA\Property(property: 'create_login', type: 'boolean', nullable: true, description: 'Crea un usuario con rol participant para el participante (requiere birth_date >= 14 años).'),
        new OA\Property(property: 'login_email', type: 'string', format: 'email', maxLength: 255, nullable: true, description: 'Email del login; si se omite se genera uno @lighthouse.local.'),
        new OA\Property(property: 'login_password', type: 'string', minLength: 8, maxLength: 255, nullable: true, description: 'Password del login; si se omite se genera una aleatoria y se retorna en la respuesta.'),
    ],
)]
class StoreParticipantRequest {}
