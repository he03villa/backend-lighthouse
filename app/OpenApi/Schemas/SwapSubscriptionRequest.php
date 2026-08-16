<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SwapSubscriptionRequest',
    type: 'object',
    required: ['plan_slug'],
    properties: [
        new OA\Property(property: 'plan_slug', type: 'string', example: 'free'),
    ],
)]
class SwapSubscriptionRequest {}
