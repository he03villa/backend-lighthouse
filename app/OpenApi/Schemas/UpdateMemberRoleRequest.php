<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateMemberRoleRequest',
    type: 'object',
    required: ['role'],
    properties: [
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'admin', 'coach', 'parent', 'participant', 'staff'], example: 'admin'),
    ],
)]
class UpdateMemberRoleRequest {}
