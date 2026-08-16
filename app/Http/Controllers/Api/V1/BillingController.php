<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\SwapSubscriptionRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PlanResource;
use App\Services\BillingService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected BillingService $service) {}

    #[OA\Get(
        path: '/api/v1/billing/plans',
        tags: ['Billing'],
        summary: 'Listar planes disponibles',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de planes', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Plan')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function plans()
    {
        try {
            return $this->successResponse(PlanResource::collection($this->service->plans()));
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list plans', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/billing/current',
        tags: ['Billing'],
        summary: 'Suscripción y uso actual del tenant',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Estado de suscripción', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'tenant', type: 'object'),
                        new OA\Property(property: 'plan', ref: '#/components/schemas/Plan'),
                        new OA\Property(property: 'subscription', type: 'object', nullable: true),
                        new OA\Property(property: 'usage', type: 'object'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function current()
    {
        try {
            return $this->successResponse($this->service->current());
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to load billing status', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/billing/subscriptions',
        tags: ['Billing'],
        summary: 'Crear suscripción (checkout de Stripe)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSubscriptionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'URL de checkout', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'url', type: 'string'),
                        new OA\Property(property: 'session_id', type: 'string'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function store(StoreSubscriptionRequest $request)
    {
        try {
            $data = $this->service->createSubscription(
                $request->validated('plan_slug'),
                $request->validated('success_url'),
                $request->validated('cancel_url'),
            );

            return $this->successResponse($data, 'Checkout session created');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create subscription', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/billing/subscriptions/swap',
        tags: ['Billing'],
        summary: 'Cambiar de plan',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SwapSubscriptionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Suscripción actualizada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function swap(SwapSubscriptionRequest $request)
    {
        try {
            return $this->successResponse($this->service->swap($request->validated('plan_slug')), 'Subscription updated');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to swap subscription', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/billing/subscriptions/cancel',
        tags: ['Billing'],
        summary: 'Cancelar suscripción (final del periodo)',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Suscripción cancelada', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object'),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function cancel()
    {
        try {
            return $this->successResponse($this->service->cancel(), 'Subscription canceled');
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to cancel subscription', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/billing/subscriptions/portal',
        tags: ['Billing'],
        summary: 'URL del billing portal de Stripe',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'URL del portal', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'url', type: 'string'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sin permisos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function portal()
    {
        try {
            return $this->successResponse(['url' => $this->service->portalUrl()]);
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to open billing portal', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/billing/invoices',
        tags: ['Billing'],
        summary: 'Listar facturas del tenant',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de facturas', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Invoice')),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 500, description: 'Error interno', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function invoices()
    {
        try {
            return $this->successResponse(InvoiceResource::collection($this->service->invoices()));
        } catch (HttpException|ModelNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list invoices', 500);
        }
    }
}
