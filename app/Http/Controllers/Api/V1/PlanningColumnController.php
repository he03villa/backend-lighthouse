<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanningColumnRequest;
use App\Http\Requests\UpdatePlanningColumnRequest;
use App\Http\Resources\PlanningColumnResource;
use App\Models\PlanningBoard;
use App\Models\PlanningColumn;
use App\Services\PlanningService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlanningColumnController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PlanningService $service) {}

    #[OA\Post(
        path: '/api/v1/planning/boards/{board}/columns',
        tags: ['Planning'],
        summary: 'Agregar columna a un tablero',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'board', in: 'path', required: true, description: 'UUID del tablero', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StorePlanningColumnRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Columna creada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningColumn'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tablero no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StorePlanningColumnRequest $request, PlanningBoard $board)
    {
        try {
            $column = $this->service->addColumn($board, $request->validated());

            return $this->successResponse(new PlanningColumnResource($column), 'Column created', 201);
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create planning column', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/planning/columns/{column}',
        tags: ['Planning'],
        summary: 'Actualizar columna',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'column', in: 'path', required: true, description: 'UUID de la columna', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdatePlanningColumnRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Columna actualizada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningColumn'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Columna no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdatePlanningColumnRequest $request, PlanningColumn $column)
    {
        try {
            return $this->successResponse(
                new PlanningColumnResource($this->service->updateColumn($column, $request->validated())),
                'Column updated',
            );
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update planning column', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/planning/columns/{column}',
        tags: ['Planning'],
        summary: 'Eliminar columna (borra sus tareas)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'column', in: 'path', required: true, description: 'UUID de la columna', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Columna eliminada', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Columna no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(PlanningColumn $column)
    {
        try {
            $this->service->deleteColumn($column);

            return $this->successResponse(null, 'Column deleted');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete planning column', 500);
        }
    }
}
