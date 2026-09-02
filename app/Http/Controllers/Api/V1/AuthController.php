<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AuthService $service) {}

    #[OA\Post(
        path: '/api/v1/auth/register',
        tags: ['Auth'],
        summary: 'Registrar usuario',
        description: 'Crea un usuario nuevo. Si se envía tenant_name, crea también un tenant y al usuario como owner.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Usuario (y tenant opcional) creado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', description: 'Token JWT a usar en Authorization: Bearer'),
                        new OA\Property(property: 'tenant', ref: '#/components/schemas/Tenant', nullable: true),
                    ]),
                ],
            )),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->service->register($request->validated());

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'tenant' => $result['tenant'] ? new TenantResource($result['tenant']) : null,
            ], 'Registration successful', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Registration failed', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        tags: ['Auth'],
        summary: 'Iniciar sesión',
        description: 'Autentica al usuario y devuelve un token JWT.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Sesión iniciada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string', description: 'Token JWT a usar en Authorization: Bearer'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'Credenciales inválidas', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->service->login($request->only('email', 'password'));

            if (! $result) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Login failed', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        tags: ['Auth'],
        summary: 'Renovar token',
        description: 'Devuelve un token JWT renovado a partir del token actual.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token renovado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'token', type: 'string', description: 'Nuevo token JWT'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado / token inválido', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function refresh()
    {
        try {
            return $this->successResponse([
                'token' => $this->service->refresh(),
            ], 'Token refreshed');
        } catch (Exception $e) {
            report($e);

            return $this->unauthorizedResponse('Token refresh failed');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        tags: ['Auth'],
        summary: 'Cerrar sesión',
        description: 'Invalida el token JWT actual.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function logout()
    {
        try {
            $this->service->logout();

            return $this->successResponse(null, 'Logged out successfully');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Logout failed', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        tags: ['Auth'],
        summary: 'Usuario autenticado',
        description: 'Devuelve la información del usuario autenticado.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Datos del usuario', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function me()
    {
        try {
            return $this->successResponse(new UserResource($this->service->me()));
        } catch (Exception $e) {
            report($e);

            return $this->unauthorizedResponse('Unauthenticated');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/accept-invitation',
        tags: ['Auth'],
        summary: 'Aceptar invitación',
        description: 'Permite al usuario invitado establecer su nombre y contraseña para acceder a la plataforma.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object',
            required: ['token', 'name', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'token', type: 'string', description: 'Token de invitación recibido por email'),
                new OA\Property(property: 'name', type: 'string', description: 'Nombre del usuario'),
                new OA\Property(property: 'password', type: 'string', description: 'Contraseña (mínimo 8 caracteres)'),
                new OA\Property(property: 'password_confirmation', type: 'string', description: 'Confirmación de contraseña'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Invitación aceptada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'token', type: 'string'),
                    ]),
                ],
            )),
            new OA\Response(response: 422, description: 'Token inválido o expirado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function acceptInvitation(AcceptInvitationRequest $request)
    {
        try {
            $validated = $request->validated();

            $result = $this->service->acceptInvitation(
                $validated['token'],
                $validated['name'],
                $validated['password']
            );

            if (! $result) {
                return $this->unauthorizedResponse('Token inválido o expirado');
            }

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Invitación aceptada exitosamente');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Error al aceptar invitación', 500);
        }
    }
}
