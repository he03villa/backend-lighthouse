<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessagingService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MessageController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected MessagingService $service) {}

    #[OA\Get(
        path: '/api/v1/conversations',
        tags: ['Messaging'],
        summary: 'Listar conversaciones',
        description: 'Lista las conversaciones del usuario autenticado.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de conversaciones'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ],
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Conversation::class);

        try {
            return $this->successResponse(
                ConversationResource::collection($this->service->listConversations($request->user())),
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list conversations', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/conversations',
        tags: ['Messaging'],
        summary: 'Crear conversación',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreConversationRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Conversación creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ],
    )]
    public function store(StoreConversationRequest $request)
    {
        $this->authorize('create', Conversation::class);

        try {
            $conversation = $this->service->createConversation(
                $request->user(),
                $request->validated('user_ids'),
                $request->validated('title'),
            );

            return $this->successResponse(new ConversationResource($conversation), 'Conversation created', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create conversation', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/conversations/{conversation}',
        tags: ['Messaging'],
        summary: 'Mostrar conversación',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conversation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de conversación'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function show(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        try {
            return $this->successResponse(
                new ConversationResource($this->service->showConversation($request->user(), $conversation)),
            );
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show conversation', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/conversations/{conversation}/messages',
        tags: ['Messaging'],
        summary: 'Enviar mensaje',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conversation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreMessageRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Mensaje enviado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function sendMessage(StoreMessageRequest $request, Conversation $conversation)
    {
        $this->authorize('sendMessage', $conversation);

        try {
            $message = $this->service->sendMessage(
                $request->user(),
                $conversation,
                $request->validated('content'),
                $request->validated('media'),
            );

            return $this->successResponse(new MessageResource($message), 'Message sent', 201);
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to send message', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/conversations/{conversation}/messages',
        tags: ['Messaging'],
        summary: 'Listar mensajes (paginados)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conversation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mensajes paginados'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function messages(Request $request, Conversation $conversation)
    {
        try {
            $paginator = $this->service->getMessages($conversation, (int) $request->query('page', 1));

            return $this->successResponse([
                'messages' => MessageResource::collection($paginator->getCollection()),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list messages', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/conversations/{conversation}/read',
        tags: ['Messaging'],
        summary: 'Marcar conversación como leída',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conversation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Marcada como leída'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function markAsRead(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        try {
            $this->service->markAsRead($request->user(), $conversation);

            return $this->successResponse(null, 'Conversation marked as read');
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to mark as read', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/conversations/{conversation}/typing',
        tags: ['Messaging'],
        summary: 'Enviar estado de typing',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'conversation', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'is_typing', type: 'boolean', example: true),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Typing status enviado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function typing(Request $request, Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        try {
            $isTyping = (bool) $request->input('is_typing', true);

            UserTyping::dispatch(
                $request->user(),
                $conversation->id,
                $isTyping,
            );

            return $this->successResponse(null, 'Typing status sent');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to send typing status', 500);
        }
    }
}
