<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFieldNoteRequest;
use App\Http\Resources\FieldNoteResource;
use App\Models\FieldNote;
use App\Models\Participant;
use App\Services\FieldNoteService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FieldNoteController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected FieldNoteService $service) {}

    #[OA\Get(
        path: '/api/v1/field-notes',
        tags: ['FieldNotes'],
        summary: 'Listar notas de campo',
        description: 'Lista las notas de campo. Si se envía participant_id, filtra por participante.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant_id', in: 'query', required: false, description: 'Filtrar por participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de notas de campo', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FieldNote')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Participante no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        try {
            $participant = $request->has('participant_id')
                ? Participant::findOrFail($request->query('participant_id'))
                : null;

            return $this->successResponse(FieldNoteResource::collection($this->service->list($participant)));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list field notes', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/field-notes',
        tags: ['FieldNotes'],
        summary: 'Crear nota de campo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreFieldNoteRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Nota de campo creada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/FieldNote'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreFieldNoteRequest $request)
    {
        try {
            $note = $this->service->create($request->user(), $request->validated());

            return $this->successResponse(new FieldNoteResource($note), 'Field note created', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create field note', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/field-notes/{fieldNote}',
        tags: ['FieldNotes'],
        summary: 'Mostrar nota de campo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'fieldNote', in: 'path', required: true, description: 'UUID de la nota de campo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de la nota de campo', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/FieldNote'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Nota de campo no encontrada', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(FieldNote $fieldNote)
    {
        try {
            return $this->successResponse(new FieldNoteResource($fieldNote->load('author', 'participant', 'submission.activity')));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show field note', 500);
        }
    }
}
