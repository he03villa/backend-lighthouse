<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AttachGuardianRequest',
    type: 'object',
    description: 'Debe enviarse user_id o email (al menos uno).',
    properties: [
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', nullable: true, description: 'Usuario existente que será guardian. Requerido si no se envía email.'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true, description: 'Email del guardian. Si no existe un usuario con ese email, se crea. Requerido si no se envía user_id.'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, nullable: true, example: 'Madre de Niño'),
        new OA\Property(property: 'relationship', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'is_primary', type: 'boolean', nullable: true, example: true),
        new OA\Property(property: 'permissions', nullable: true),
    ],
)]
class AttachGuardianRequest {}
