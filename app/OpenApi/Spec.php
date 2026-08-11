<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Lighthouse API',
    version: '1.0.0',
    description: "API REST para la plataforma Lighthouse (programas socioeducativos, participantes, evidencias y notas de campo).\n\n".
        'La API es multi-tenant: tras autenticarte obtienes un token JWT y envías el header `Authorization: Bearer <token>`. '.
        'Los recursos scoped (participants, groups, programs, ...) viven dentro de un tenant activo, definido por la sesión del usuario autenticado.',
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Lighthouse API')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token JWT devuelto por `POST /api/v1/auth/login` (o `register`). Formato: `Authorization: Bearer <token>`.',
)]
#[OA\Tag(name: 'Auth', description: 'Autenticación y cuenta (JWT)')]
#[OA\Tag(name: 'Tenants', description: 'Gestión de tenants')]
#[OA\Tag(name: 'Members', description: 'Miembros de un tenant y roles')]
#[OA\Tag(name: 'Participants', description: 'Participantes y sus guardians')]
#[OA\Tag(name: 'Groups', description: 'Grupos de participantes')]
#[OA\Tag(name: 'Programs', description: 'Programas, módulos y actividades')]
#[OA\Tag(name: 'Enrollments', description: 'Inscripciones de participantes a programas')]
#[OA\Tag(name: 'Submissions', description: 'Evidencias y revisión de actividades')]
#[OA\Tag(name: 'FieldNotes', description: 'Notas de campo')]
class Spec {}
