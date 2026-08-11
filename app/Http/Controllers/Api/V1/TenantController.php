<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TenantController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected TenantService $service) {}

    #[OA\Get(
        path: '/api/v1/tenants',
        tags: ['Tenants'],
        summary: 'Listar tenants del usuario',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de tenants', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Tenant')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request)
    {
        try {
            $tenants = $this->service->listFor($request->user());

            return $this->successResponse(TenantResource::collection($tenants));
        } catch (Exception $e) {
            return $this->errorResponse('Failed to list tenants', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/tenants',
        tags: ['Tenants'],
        summary: 'Crear tenant',
        description: 'Crea un nuevo tenant y asigna al usuario autenticado como owner.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreTenantRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Tenant creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tenant'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreTenantRequest $request)
    {
        try {
            $tenant = $this->service->create($request->user(), $request->validated());

            return $this->successResponse(new TenantResource($tenant), 'Tenant created', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create tenant', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/tenants/{tenant}',
        tags: ['Tenants'],
        summary: 'Mostrar tenant',
        description: 'Devuelve un tenant siempre que el usuario autenticado sea miembro de él.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, description: 'UUID del tenant', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del tenant', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tenant'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'No es miembro del tenant', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Tenant no encontrado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Request $request, Tenant $tenant)
    {
        try {
            $this->service->assertMember($tenant, $request->user());

            return $this->successResponse(new TenantResource($tenant));
        } catch (Exception $e) {
            throw $e;
        }
    }
}
