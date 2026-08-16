<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovePlanningTaskRequest;
use App\Http\Requests\StorePlanningTaskRequest;
use App\Http\Requests\UpdatePlanningTaskRequest;
use App\Http\Resources\PlanningTaskResource;
use App\Models\PlanningColumn;
use App\Models\PlanningTask;
use App\Services\PlanningService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlanningTaskController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PlanningService $service) {}

    #[OA\Post(
        path: '/api/v1/planning/columns/{column}/tasks',
        tags: ['Planning'],
        summary: 'Agregar tarea a una columna',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'column', in: 'path', required: true, description: 'UUID de la columna', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StorePlanningTaskRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Tarea creada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningTask'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Columna no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StorePlanningTaskRequest $request, PlanningColumn $column)
    {
        try {
            $task = $this->service->addTask($column, $request->validated());

            return $this->successResponse(new PlanningTaskResource($task), 'Task created', 201);
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create planning task', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/planning/tasks/{task}',
        tags: ['Planning'],
        summary: 'Actualizar tarea',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, description: 'UUID de la tarea', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdatePlanningTaskRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Tarea actualizada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningTask'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tarea no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdatePlanningTaskRequest $request, PlanningTask $task)
    {
        try {
            return $this->successResponse(
                new PlanningTaskResource($this->service->updateTask($task, $request->validated())),
                'Task updated',
            );
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update planning task', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/planning/tasks/{task}/move',
        tags: ['Planning'],
        summary: 'Mover tarea entre columnas o reordenar',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, description: 'UUID de la tarea', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MovePlanningTaskRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Tarea movida', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningTask'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tarea no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function move(MovePlanningTaskRequest $request, PlanningTask $task)
    {
        try {
            $data = $request->validated();

            return $this->successResponse(
                new PlanningTaskResource($this->service->moveTask($task, $data['column_id'] ?? null, $data['position'] ?? null)),
                'Task moved',
            );
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to move planning task', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/planning/tasks/{task}',
        tags: ['Planning'],
        summary: 'Eliminar tarea',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'task', in: 'path', required: true, description: 'UUID de la tarea', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tarea eliminada', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tarea no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(PlanningTask $task)
    {
        try {
            $this->service->deleteTask($task);

            return $this->successResponse(null, 'Task deleted');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete planning task', 500);
        }
    }
}
