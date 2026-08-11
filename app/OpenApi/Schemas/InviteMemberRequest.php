<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InviteMemberRequest',
    type: 'object',
    required: ['email', 'role'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'nuevo@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'admin', 'coach', 'parent', 'participant', 'staff'], example: 'coach'),
    ],
)]
class InviteMemberRequest {}
