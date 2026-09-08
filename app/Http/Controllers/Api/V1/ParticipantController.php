<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachGuardianRequest;
use App\Http\Requests\StoreParticipantRequest;
use App\Http\Requests\UpdateParticipantRequest;
use App\Http\Resources\FieldNoteResource;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Models\User;
use App\Services\FieldNoteService;
use App\Services\ParticipantService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ParticipantController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ParticipantService $service,
        protected FieldNoteService $fieldNotes,
    ) {}

    #[OA\Get(
        path: '/api/v1/participants',
        tags: ['Participants'],
        summary: 'Listar participantes',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de participantes', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Participant')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index()
    {
        $this->authorize('viewAny', Participant::class);

        try {
            return $this->successResponse(ParticipantResource::collection($this->service->list()));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list participants', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/my/participants',
        tags: ['Participants'],
        summary: 'Mis participantes',
        description: 'Retorna los participantes según el rol del usuario: coach/admin ve todos, parent ve sus hijos, participant ve solo sí mismo.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de participantes', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Participant')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function myParticipants(Request $request)
    {
        try {
            $participants = $this->service->myParticipants($request->user());

            return $this->successResponse(ParticipantResource::collection($participants));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list participants', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/participants',
        tags: ['Participants'],
        summary: 'Crear participante',
        description: 'Crea un participante. Los guardians incluidos se crean como usuarios y se agregan como miembros con rol parent. Con create_login se crea un usuario con rol participant (requiere birth_date >= 14 años); si la password no se envía se genera una y se retorna en data.login.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreParticipantRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Participante creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Participant'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreParticipantRequest $request)
    {
        try {
            $result = $this->service->create($request->validated());

            $data = new ParticipantResource($result['participant']);

            if ($result['login']) {
                $data = array_merge($data->resolve(), ['login' => $result['login']]);
            }

            return $this->successResponse($data, 'Participant created', 201);
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create participant', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/participants/{participant}',
        tags: ['Participants'],
        summary: 'Mostrar participante',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del participante (incluye guardians y grupos)', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Participant'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Participante no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Participant $participant)
    {
        $this->authorize('view', $participant);

        try {
            return $this->successResponse(new ParticipantResource(
                $participant->load('guardians', 'groups', 'enrollments.program', 'enrollments.progressRecord')
            ));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show participant', 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/participants/{participant}',
        tags: ['Participants'],
        summary: 'Actualizar participante',
        description: 'Actualiza los datos del participante. Todos los campos son opcionales.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreParticipantRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Participante actualizado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Participant'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Participante no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateParticipantRequest $request, Participant $participant)
    {
        $this->authorize('update', $participant);

        try {
            return $this->successResponse(
                new ParticipantResource($this->service->update($participant, $request->validated())),
                'Participant updated',
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update participant', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/participants/{participant}',
        tags: ['Participants'],
        summary: 'Eliminar participante',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Participante eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Participante no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Participant $participant)
    {
        $this->authorize('delete', $participant);

        try {
            $this->service->delete($participant);

            return $this->successResponse(null, 'Participant deleted');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete participant', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/participants/{participant}/guardians',
        tags: ['Participants'],
        summary: 'Agregar guardian',
        description: 'Asocia un usuario como guardian del participante (se envía user_id o email).',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AttachGuardianRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Guardian agregado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Participant'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function addGuardian(AttachGuardianRequest $request, Participant $participant)
    {
        $this->authorize('addGuardian', $participant);

        try {
            $data = $request->validated();

            $user = isset($data['user_id'])
                ? User::findOrFail($data['user_id'])
                : User::firstOrCreate(
                    ['email' => $data['email']],
                    ['name' => $data['name'] ?? 'Guardian'],
                );

            $participant = $this->service->addGuardian($participant, $user, $data);

            return $this->successResponse(new ParticipantResource($participant), 'Guardian added');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to add guardian', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/participants/{participant}/guardians/{user}',
        tags: ['Participants'],
        summary: 'Quitar guardian',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'UUID del guardian (usuario)', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Guardian eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Participante o guardian no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function removeGuardian(Participant $participant, User $user)
    {
        $this->authorize('removeGuardian', $participant);

        try {
            $this->service->removeGuardian($participant, $user);

            return $this->successResponse(null, 'Guardian removed');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to remove guardian', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/participants/{participant}/field-notes',
        tags: ['Participants', 'FieldNotes'],
        summary: 'Notas de campo del participante',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
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
        ],
    )]
    public function fieldNotes(Participant $participant)
    {
        try {
            return $this->successResponse(FieldNoteResource::collection($this->fieldNotes->list(auth()->user(), $participant)));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list field notes', 500);
        }
    }
}
