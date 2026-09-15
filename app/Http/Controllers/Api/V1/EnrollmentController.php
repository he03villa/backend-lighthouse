<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\ProgressResource;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use App\Services\ProgressService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EnrollmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected EnrollmentService $service,
        protected ProgressService $progress,
    ) {}

    #[OA\Get(
        path: '/api/v1/enrollments',
        tags: ['Enrollments'],
        summary: 'Listar inscripciones',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de inscripciones', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Enrollment')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Enrollment::class);

        try {
            $participantId = $request->query('participant_id');

            return $this->successResponse(EnrollmentResource::collection($this->service->list($participantId, $request->user())));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list enrollments', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/enrollments',
        tags: ['Enrollments'],
        summary: 'Inscribir participante a un programa',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreEnrollmentRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Inscripción creada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Enrollment'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreEnrollmentRequest $request)
    {
        try {
            return $this->successResponse(
                new EnrollmentResource($this->service->create($request->validated())),
                'Enrollment created',
                201,
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create enrollment', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/enrollments/{enrollment}',
        tags: ['Enrollments'],
        summary: 'Mostrar inscripción',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'enrollment', in: 'path', required: true, description: 'UUID de la inscripción', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la inscripción (participante, programa y progreso)', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Enrollment'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Inscripción no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Enrollment $enrollment)
    {
        $this->authorize('view', $enrollment);

        try {
            return $this->successResponse(
                new EnrollmentResource($enrollment->load('participant', 'program', 'progressRecord')),
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show enrollment', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/enrollments/{enrollment}',
        tags: ['Enrollments'],
        summary: 'Dar de baja la inscripción',
        description: 'Marca la inscripción como dada de baja (dropped).',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'enrollment', in: 'path', required: true, description: 'UUID de la inscripción', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inscripción dada de baja', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Inscripción no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('delete', $enrollment);

        try {
            $this->service->delete($enrollment);

            return $this->successResponse(null, 'Enrollment dropped');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to drop enrollment', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/enrollments/{enrollment}/progress',
        tags: ['Enrollments'],
        summary: 'Progreso de la inscripción',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'enrollment', in: 'path', required: true, description: 'UUID de la inscripción', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Progreso de la inscripción', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Progress'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Inscripción no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function progress(Enrollment $enrollment)
    {
        $this->authorize('viewProgress', $enrollment);

        try {
            $enrollment->load('progressRecord');

            $record = $enrollment->progressRecord
                ? $enrollment->progressRecord
                : $this->progress->initializeFor($enrollment);

            return $this->successResponse(new ProgressResource($record));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show progress', 500);
        }
    }
}
