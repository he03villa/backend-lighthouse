<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ReviewSubmissionRequest',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['approved', 'rejected'], example: 'approved'),
        new OA\Property(property: 'observation', type: 'string', nullable: true),
    ],
)]
class ReviewSubmissionRequest {}
