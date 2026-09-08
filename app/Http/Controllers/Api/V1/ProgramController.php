<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\StoreThumbnailRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use App\Services\ProgramService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProgramController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ProgramService $service) {}

    #[OA\Get(
        path: '/api/v1/programs',
        tags: ['Programs'],
        summary: 'Listar programas',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'published',
                description: 'Filtrar solo programas publicados (true/false).',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de programas', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Program')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Program::class);

        try {
            $published = $request->filled('published') ? $request->boolean('published') : null;

            return $this->successResponse(ProgramResource::collection($this->service->list($published)));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list programs', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/programs',
        tags: ['Programs'],
        summary: 'Crear programa',
        description: 'Crea un programa, opcionalmente con módulos y actividades anidados.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProgramRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Programa creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Program'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[OA\Post(
        path: '/api/v1/programs/thumbnail',
        tags: ['Programs'],
        summary: 'Subir portada de programa',
        description: 'Sube una imagen de portada y devuelve su URL pública para guardarla en el programa.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['thumbnail'],
                properties: [
                    new OA\Property(property: 'thumbnail', type: 'string', format: 'binary'),
                ],
            ),
        )),
        responses: [
            new OA\Response(response: 200, description: 'URL de la portada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'url', type: 'string'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function uploadThumbnail(StoreThumbnailRequest $request)
    {
        $this->authorize('create', Program::class);

        try {
            $url = $this->service->storeThumbnail($request->validated()['thumbnail']);

            return $this->successResponse(['url' => $url], 'Thumbnail uploaded');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to upload thumbnail', 500);
        }
    }

    public function store(StoreProgramRequest $request)
    {
        try {
            return $this->successResponse(
                new ProgramResource($this->service->create($request->validated())),
                'Program created',
                201,
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create program', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/programs/{program}',
        tags: ['Programs'],
        summary: 'Mostrar programa',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del programa (incluye módulos y actividades)', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Program'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Program $program)
    {
        $this->authorize('view', $program);

        try {
            return $this->successResponse(new ProgramResource($program->load('modules.activities')));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show program', 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/programs/{program}',
        tags: ['Programs'],
        summary: 'Actualizar programa',
        description: 'Actualiza un programa (todos los campos opcionales), incluyendo sus módulos y actividades anidados.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProgramRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Programa actualizado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Program'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateProgramRequest $request, Program $program)
    {
        $this->authorize('update', $program);

        try {
            return $this->successResponse(
                new ProgramResource($this->service->update($program, $request->validated())),
                'Program updated',
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update program', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/programs/{program}',
        tags: ['Programs'],
        summary: 'Eliminar programa',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Programa eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);

        try {
            $this->service->delete($program);

            return $this->successResponse(null, 'Program deleted');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete program', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/programs/{program}/publish',
        tags: ['Programs'],
        summary: 'Publicar programa',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Programa publicado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Program'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function publish(Program $program)
    {
        $this->authorize('publish', $program);

        try {
            return $this->successResponse(new ProgramResource($this->service->publish($program)), 'Program published');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to publish program', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/programs/{program}/unpublish',
        tags: ['Programs'],
        summary: 'Despublicar programa',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'program', in: 'path', required: true, description: 'UUID del programa', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Programa despublicado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Program'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Programa no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function unpublish(Program $program)
    {
        $this->authorize('publish', $program);

        try {
            return $this->successResponse(new ProgramResource($this->service->unpublish($program)), 'Program unpublished');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to unpublish program', 500);
        }
    }
}
