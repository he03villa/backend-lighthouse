<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubmitActivityRequest',
    type: 'object',
    required: ['enrollment_id'],
    properties: [
        new OA\Property(property: 'enrollment_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'evidence_type', type: 'string', enum: ['text', 'image', 'video', 'audio', 'file'], nullable: true),
        new OA\Property(property: 'content', type: 'string', nullable: true, description: 'Contenido de la evidencia. Requerido si no se envía evidence_file.'),
        new OA\Property(property: 'evidence_file', type: 'string', format: 'binary', nullable: true, description: 'Archivo de evidencia (multipart/form-data).'),
        new OA\Property(property: 'metadata', nullable: true),
    ],
)]
class SubmitActivityRequest {}
