<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreTenantRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Club Deportivo'),
        new OA\Property(property: 'slug', type: 'string', maxLength: 255, nullable: true, description: 'Si no se envía, se genera automáticamente.'),
        new OA\Property(property: 'type', type: 'string', enum: ['individual', 'organization', 'family'], nullable: true),
        new OA\Property(property: 'config', nullable: true),
    ],
)]
class StoreTenantRequest {}
