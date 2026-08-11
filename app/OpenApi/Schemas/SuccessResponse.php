<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    description: 'Respuesta estándar de la API.',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'OK'),
        new OA\Property(property: 'data', nullable: true, description: 'Payload de la operación. Su forma depende del endpoint.'),
    ],
)]
class SuccessResponse {}
