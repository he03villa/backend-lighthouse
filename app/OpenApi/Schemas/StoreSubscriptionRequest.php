<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreSubscriptionRequest',
    type: 'object',
    required: ['plan_slug', 'success_url', 'cancel_url'],
    properties: [
        new OA\Property(property: 'plan_slug', type: 'string', example: 'pro'),
        new OA\Property(property: 'success_url', type: 'string', format: 'url'),
        new OA\Property(property: 'cancel_url', type: 'string', format: 'url'),
    ],
)]
class StoreSubscriptionRequest {}
