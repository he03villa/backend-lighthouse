<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Http\Resources\MemberResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\Permission\PermissionRegistrar;

class MemberController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected TenantService $service,
        protected PermissionRegistrar $permissions,
    ) {}

    #[OA\Get(
        path: '/api/v1/tenants/{tenant}/members',
        tags: ['Members'],
        summary: 'Listar miembros del tenant',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, description: 'UUID del tenant', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de miembros', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Member')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'No es miembro del tenant', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tenant no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request, Tenant $tenant)
    {
        try {
            $this->service->assertMember($tenant, $request->user());

            $this->permissions->setPermissionsTeamId($tenant->id);
            $members = $this->service->members($tenant)->load('roles');

            return $this->successResponse(MemberResource::collection($members));
        } catch (Exception $e) {
            throw $e;
        }
    }

    #[OA\Post(
        path: '/api/v1/tenants/{tenant}/members',
        tags: ['Members'],
        summary: 'Invitar miembro',
        description: 'Invita a un usuario al tenant (por email) asignándole un rol. Si el usuario no existe, se crea.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, description: 'UUID del tenant', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InviteMemberRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Miembro invitado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Member'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos para invitar', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function invite(InviteMemberRequest $request, Tenant $tenant)
    {
        try {
            $member = $this->service->invite(
                $request->user(),
                $tenant,
                $request->validated('email'),
                $request->validated('role'),
            );

            $this->permissions->setPermissionsTeamId($tenant->id);

            return $this->successResponse(new MemberResource($member->load('roles')), 'Member invited', 201);
        } catch (Exception $e) {
            throw $e;
        }
    }

    #[OA\Patch(
        path: '/api/v1/tenants/{tenant}/members/{user}',
        tags: ['Members'],
        summary: 'Actualizar rol de miembro',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, description: 'UUID del tenant', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'UUID del usuario', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateMemberRoleRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Rol actualizado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Member'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tenant o usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function updateRole(UpdateMemberRoleRequest $request, Tenant $tenant, User $user)
    {
        try {
            $member = $this->service->updateRole(
                $request->user(),
                $tenant,
                $user,
                $request->validated('role'),
            );

            $this->permissions->setPermissionsTeamId($tenant->id);

            return $this->successResponse(new MemberResource($member->load('roles')), 'Role updated');
        } catch (Exception $e) {
            throw $e;
        }
    }

    #[OA\Delete(
        path: '/api/v1/tenants/{tenant}/members/{user}',
        tags: ['Members'],
        summary: 'Eliminar miembro',
        description: 'Remueve a un usuario del tenant.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, description: 'UUID del tenant', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'UUID del usuario', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Miembro eliminado', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tenant o usuario no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function remove(Request $request, Tenant $tenant, User $user)
    {
        try {
            $this->service->remove($request->user(), $tenant, $user);

            return $this->successResponse(null, 'Member removed');
        } catch (Exception $e) {
            throw $e;
        }
    }
}
