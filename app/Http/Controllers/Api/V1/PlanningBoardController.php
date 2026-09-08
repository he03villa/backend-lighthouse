<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanningBoardRequest;
use App\Http\Requests\UpdatePlanningBoardRequest;
use App\Http\Resources\PlanningBoardResource;
use App\Models\PlanningBoard;
use App\Services\PlanningService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlanningBoardController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PlanningService $service) {}

    #[OA\Get(
        path: '/api/v1/planning/boards',
        tags: ['Planning'],
        summary: 'Listar tableros de planificación',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de tableros', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PlanningBoard')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', PlanningBoard::class);

        try {
            return $this->successResponse(PlanningBoardResource::collection($this->service->listBoards()));
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list planning boards', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/planning/boards',
        tags: ['Planning'],
        summary: 'Crear tablero de planificación',
        description: 'Crea un tablero y agrega las columnas por defecto (Backlog, In Progress, Done).',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StorePlanningBoardRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Tablero creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningBoard'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StorePlanningBoardRequest $request)
    {
        $this->authorize('create', PlanningBoard::class);

        try {
            $board = $this->service->createBoard($request->validated());

            return $this->successResponse(new PlanningBoardResource($board->load('columns')), 'Board created', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create planning board', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/planning/boards/{board}',
        tags: ['Planning'],
        summary: 'Mostrar tablero con columnas y tareas',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'board', in: 'path', required: true, description: 'UUID del tablero', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del tablero', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningBoard'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tablero no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Request $request, PlanningBoard $board)
    {
        $this->authorize('view', $board);

        try {
            return $this->successResponse(new PlanningBoardResource($this->service->showBoard($board)));
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show planning board', 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/planning/boards/{board}',
        tags: ['Planning'],
        summary: 'Actualizar tablero de planificación',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'board', in: 'path', required: true, description: 'UUID del tablero', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdatePlanningBoardRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Tablero actualizado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PlanningBoard'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tablero no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function update(UpdatePlanningBoardRequest $request, PlanningBoard $board)
    {
        $this->authorize('update', $board);

        try {
            return $this->successResponse(
                new PlanningBoardResource($this->service->updateBoard($board, $request->validated())),
                'Board updated',
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update planning board', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/planning/boards/{board}',
        tags: ['Planning'],
        summary: 'Eliminar tablero de planificación',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'board', in: 'path', required: true, description: 'UUID del tablero', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tablero eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tablero no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Request $request, PlanningBoard $board)
    {
        $this->authorize('delete', $board);

        try {
            $this->service->deleteBoard($board);

            return $this->successResponse(null, 'Board deleted');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete planning board', 500);
        }
    }
}
