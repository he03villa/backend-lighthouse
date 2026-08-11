<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSubmissionRequest;
use App\Http\Requests\SubmitActivityRequest;
use App\Http\Resources\ActivitySubmissionResource;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Enrollment;
use App\Services\SubmissionService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubmissionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected SubmissionService $service) {}

    #[OA\Get(
        path: '/api/v1/submissions',
        tags: ['Submissions'],
        summary: 'Listar evidencias',
        description: 'Lista las evidencias (submissions). Filtros opcionales por status, enrollment_id o activity_id.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, description: 'Filtrar por estado', schema: new OA\Schema(type: 'string', enum: ['pending', 'submitted', 'reviewed', 'approved', 'rejected'])),
            new OA\Parameter(name: 'enrollment_id', in: 'query', required: false, description: 'Filtrar por inscripción', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'activity_id', in: 'query', required: false, description: 'Filtrar por actividad', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de evidencias', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ActivitySubmission')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        try {
            $submissions = $this->service->list($request->only(['status', 'enrollment_id', 'activity_id']));

            return $this->successResponse(ActivitySubmissionResource::collection($submissions));
        } catch (Exception $e) {
            return $this->errorResponse('Failed to list submissions', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/submissions/{submission}',
        tags: ['Submissions'],
        summary: 'Mostrar evidencia',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, description: 'UUID de la evidencia', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la evidencia (evidencias, actividad e inscripción)', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ActivitySubmission'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Evidencia no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(ActivitySubmission $submission)
    {
        try {
            return $this->successResponse(new ActivitySubmissionResource($submission->load('evidences', 'activity', 'enrollment.participant')));
        } catch (Exception $e) {
            return $this->errorResponse('Failed to show submission', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/activities/{activity}/submit',
        tags: ['Submissions'],
        summary: 'Enviar evidencia de actividad',
        description: 'Envía una evidencia (texto o archivo multipart) para una actividad dentro de una inscripción.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'activity', in: 'path', required: true, description: 'UUID de la actividad', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: '#/components/schemas/SubmitActivityRequest'),
        )),
        responses: [
            new OA\Response(response: 201, description: 'Evidencia enviada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ActivitySubmission'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Actividad o inscripción no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function submit(SubmitActivityRequest $request, Activity $activity)
    {
        try {
            $enrollment = Enrollment::findOrFail($request->validated('enrollment_id'));

            $submission = $this->service->submit($request->user(), $activity, $enrollment, $request->validated());

            return $this->successResponse(new ActivitySubmissionResource($submission), 'Evidence submitted', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to submit evidence', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/submissions/{submission}/review',
        tags: ['Submissions'],
        summary: 'Revisar evidencia',
        description: 'Aprueba o rechaza una evidencia.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'submission', in: 'path', required: true, description: 'UUID de la evidencia', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReviewSubmissionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Evidencia revisada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ActivitySubmission'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Evidencia no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function review(ReviewSubmissionRequest $request, ActivitySubmission $submission)
    {
        try {
            $submission = $this->service->review($submission, $request->user(), $request->validated());

            return $this->successResponse(new ActivitySubmissionResource($submission), 'Submission reviewed');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to review submission', 500);
        }
    }
}
