<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Requests\UpdateModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Models\Module;
use App\Models\Program;
use App\Services\ProgramService;
use App\Traits\ApiResponseTrait;
use Exception;
use OpenApi\Attributes as OA;

class ModuleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ProgramService $service) {}

    #[OA\Post(
        path: '/api/v1/programs/{program}/modules',
        tags: ['Programs'],
        summary: 'Agregar módulo a un programa',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreModuleRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Módulo creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Module'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function store(StoreModuleRequest $request, Program $program)
    {
        try {
            return $this->successResponse(
                new ModuleResource($this->service->addModule($program, $request->validated())),
                'Module created',
                201,
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create module', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/programs/{program}/modules/{module}',
        tags: ['Programs'],
        summary: 'Actualizar módulo',
        description: 'Actualiza un módulo. Todos los campos son opcionales.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'module', in: 'path', required: true, description: 'UUID del módulo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreModuleRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Módulo actualizado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Module'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa o módulo no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateModuleRequest $request, Program $program, Module $module)
    {
        try {
            return $this->successResponse(
                new ModuleResource($this->service->updateModule($program, $module, $request->validated())),
                'Module updated',
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update module', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/programs/{program}/modules/{module}',
        tags: ['Programs'],
        summary: 'Eliminar módulo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'module', in: 'path', required: true, description: 'UUID del módulo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Módulo eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa o módulo no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Program $program, Module $module)
    {
        try {
            $this->service->deleteModule($program, $module);

            return $this->successResponse(null, 'Module deleted');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete module', 500);
        }
    }
}
