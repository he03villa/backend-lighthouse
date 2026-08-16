<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Plan',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Pro'),
        new OA\Property(property: 'slug', type: 'string', example: 'pro'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price_monthly', type: 'integer', example: 4900),
        new OA\Property(property: 'price_yearly', type: 'integer', example: 49000),
        new OA\Property(property: 'currency', type: 'string', example: 'usd'),
        new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        new OA\Property(property: 'limits', type: 'object', nullable: true),
    ],
)]
class Plan {}
