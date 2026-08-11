<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddGroupMemberRequest;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\Participant;
use App\Services\GroupService;
use App\Traits\ApiResponseTrait;
use Exception;
use OpenApi\Attributes as OA;

class GroupController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected GroupService $service) {}

    #[OA\Get(
        path: '/api/v1/groups',
        tags: ['Groups'],
        summary: 'Listar grupos',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de grupos', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Group')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index()
    {
        try {
            return $this->successResponse(GroupResource::collection($this->service->list()));
        } catch (Exception $e) {
            return $this->errorResponse('Failed to list groups', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/groups',
        tags: ['Groups'],
        summary: 'Crear grupo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreGroupRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Grupo creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Group'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreGroupRequest $request)
    {
        try {
            return $this->successResponse(new GroupResource($this->service->create($request->validated())), 'Group created', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create group', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/groups/{group}',
        tags: ['Groups'],
        summary: 'Mostrar grupo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'group', in: 'path', required: true, description: 'UUID del grupo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del grupo (incluye participantes)', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Group'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Grupo no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Group $group)
    {
        try {
            return $this->successResponse(new GroupResource($group->load('participants')));
        } catch (Exception $e) {
            return $this->errorResponse('Failed to show group', 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/groups/{group}',
        tags: ['Groups'],
        summary: 'Actualizar grupo',
        description: 'Actualiza el nombre del grupo. El campo es opcional.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'group', in: 'path', required: true, description: 'UUID del grupo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreGroupRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Grupo actualizado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Group'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Grupo no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function update(UpdateGroupRequest $request, Group $group)
    {
        try {
            return $this->successResponse(
                new GroupResource($this->service->update($group, $request->validated())),
                'Group updated',
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update group', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/groups/{group}',
        tags: ['Groups'],
        summary: 'Eliminar grupo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'group', in: 'path', required: true, description: 'UUID del grupo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Grupo eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Grupo no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Group $group)
    {
        try {
            $this->service->delete($group);

            return $this->successResponse(null, 'Group deleted');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete group', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/groups/{group}/members',
        tags: ['Groups'],
        summary: 'Agregar participante al grupo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'group', in: 'path', required: true, description: 'UUID del grupo', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AddGroupMemberRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Participante agregado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Group'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Grupo o participante no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function addMember(AddGroupMemberRequest $request, Group $group)
    {
        try {
            $participant = Participant::findOrFail($request->validated('participant_id'));

            return $this->successResponse(
                new GroupResource($this->service->addMember($group, $participant)),
                'Member added',
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to add member', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/groups/{group}/members/{participant}',
        tags: ['Groups'],
        summary: 'Quitar participante del grupo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'group', in: 'path', required: true, description: 'UUID del grupo', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'participant', in: 'path', required: true, description: 'UUID del participante', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Participante eliminado del grupo', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Group'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Grupo o participante no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function removeMember(Group $group, Participant $participant)
    {
        try {
            return $this->successResponse(
                new GroupResource($this->service->removeMember($group, $participant)),
                'Member removed',
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to remove member', 500);
        }
    }
}
