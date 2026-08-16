<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Invoice',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'stripe_invoice_id', type: 'string'),
        new OA\Property(property: 'amount', type: 'integer'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'hosted_invoice_url', type: 'string', nullable: true),
        new OA\Property(property: 'invoice_pdf', type: 'string', nullable: true),
        new OA\Property(property: 'due_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class Invoice {}
