<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalRequest;
use App\Http\Requests\UpdateJournalRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\JournalService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JournalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected JournalService $service) {}

    #[OA\Get(
        path: '/api/v1/journal',
        tags: ['Journal'],
        summary: 'Listar entradas de bitácora',
        description: 'Filtros opcionales: participant_id, from y to. El visor solo ve las entradas que puede leer.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant_id', in: 'query', required: false, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'from', in: 'query', required: false, description: 'Fecha inicial (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, description: 'Fecha final (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de entradas', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/JournalEntry')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        try {
            $data = $this->service->list(
                $request->user(),
                $request->query('participant_id'),
                $request->query('from'),
                $request->query('to'),
            );

            return $this->successResponse(JournalEntryResource::collection($data));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list journal entries', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/journal',
        tags: ['Journal'],
        summary: 'Crear entrada de bitácora',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreJournalRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Entrada creada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/JournalEntry'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreJournalRequest $request)
    {
        try {
            $entry = $this->service->create($request->user(), $request->validated());

            return $this->successResponse(new JournalEntryResource($entry), 'Journal entry created', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create journal entry', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/journal/{entry}',
        tags: ['Journal'],
        summary: 'Mostrar entrada de bitácora',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'entry', in: 'path', required: true, description: 'UUID de la entrada', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la entrada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/JournalEntry'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Entrada no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Request $request, JournalEntry $entry)
    {
        try {
            return $this->successResponse(new JournalEntryResource($this->service->show($request->user(), $entry)));
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show journal entry', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/journal/{entry}',
        tags: ['Journal'],
        summary: 'Actualizar entrada de bitácora',
        description: 'Solo el autor de la entrada puede actualizarla.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'entry', in: 'path', required: true, description: 'UUID de la entrada', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateJournalRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Entrada actualizada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/JournalEntry'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Entrada no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateJournalRequest $request, JournalEntry $entry)
    {
        try {
            return $this->successResponse(
                new JournalEntryResource($this->service->update($request->user(), $entry, $request->validated())),
                'Journal entry updated',
            );
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update journal entry', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/journal/{entry}',
        tags: ['Journal'],
        summary: 'Eliminar entrada de bitácora',
        description: 'Solo el autor de la entrada puede eliminarla.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'entry', in: 'path', required: true, description: 'UUID de la entrada', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Entrada eliminada', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Entrada no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Request $request, JournalEntry $entry)
    {
        try {
            $this->service->delete($request->user(), $entry);

            return $this->successResponse(null, 'Journal entry deleted');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete journal entry', 500);
        }
    }
}
