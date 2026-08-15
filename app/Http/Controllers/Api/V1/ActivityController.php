<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Module;
use App\Services\ProgramService;
use App\Traits\ApiResponseTrait;
use Exception;
use OpenApi\Attributes as OA;

class ActivityController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ProgramService $service) {}

    #[OA\Post(
        path: '/api/v1/modules/{module}/activities',
        tags: ['Programs'],
        summary: 'Agregar actividad a un módulo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'module', in: 'path', required: true, description: 'UUID del módulo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreActivityRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Actividad creada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Activity'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Módulo no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreActivityRequest $request, Module $module)
    {
        try {
            return $this->successResponse(
                new ActivityResource($this->service->addActivity($module, $request->validated())),
                'Activity created',
                201,
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create activity', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/modules/{module}/activities/{activity}',
        tags: ['Programs'],
        summary: 'Actualizar actividad',
        description: 'Actualiza una actividad. Todos los campos son opcionales.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'module', in: 'path', required: true, description: 'UUID del módulo', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'activity', in: 'path', required: true, description: 'UUID de la actividad', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreActivityRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Actividad actualizada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Activity'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Módulo o actividad no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateActivityRequest $request, Module $module, Activity $activity)
    {
        try {
            return $this->successResponse(
                new ActivityResource($this->service->updateActivity($module, $activity, $request->validated())),
                'Activity updated',
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update activity', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/modules/{module}/activities/{activity}',
        tags: ['Programs'],
        summary: 'Eliminar actividad',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'module', in: 'path', required: true, description: 'UUID del módulo', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'activity', in: 'path', required: true, description: 'UUID de la actividad', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Actividad eliminada', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Módulo o actividad no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Module $module, Activity $activity)
    {
        try {
            $this->service->deleteActivity($module, $activity);

            return $this->successResponse(null, 'Activity deleted');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete activity', 500);
        }
    }
}
