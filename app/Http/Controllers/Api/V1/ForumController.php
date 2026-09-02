<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreForumCommentRequest;
use App\Http\Requests\StoreForumPostRequest;
use App\Http\Requests\StoreForumReactionRequest;
use App\Http\Requests\UpdateForumPostRequest;
use App\Http\Resources\ForumCommentResource;
use App\Http\Resources\ForumPostResource;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Services\ForumService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ForumController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ForumService $service) {}

    #[OA\Get(
        path: '/api/v1/forum/posts',
        tags: ['Forum'],
        summary: 'Listar posts del foro',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista paginada de posts'),
        ],
    )]
    public function index(Request $request)
    {
        try {
            return $this->successResponse(
                ForumPostResource::collection($this->service->listPosts($request->query('category'))),
            );
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to list forum posts', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/forum/posts',
        tags: ['Forum'],
        summary: 'Crear post en el foro',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreForumPostRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Post creado'),
        ],
    )]
    public function store(StoreForumPostRequest $request)
    {
        try {
            $post = $this->service->createPost($request->user(), $request->validated());

            return $this->successResponse(new ForumPostResource($post), 'Post created', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create forum post', 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/forum/posts/{post}',
        tags: ['Forum'],
        summary: 'Mostrar post con comentarios y reacciones',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle del post'),
        ],
    )]
    public function show(ForumPost $post)
    {
        try {
            return $this->successResponse(new ForumPostResource($this->service->showPost($post)));
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to show forum post', 500);
        }
    }

    #[OA\Patch(
        path: '/api/v1/forum/posts/{post}',
        tags: ['Forum'],
        summary: 'Actualizar post',
        description: 'Solo el autor o un admin pueden actualizar.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateForumPostRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Post actualizado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function update(UpdateForumPostRequest $request, ForumPost $post)
    {
        try {
            return $this->successResponse(
                new ForumPostResource($this->service->updatePost($request->user(), $post, $request->validated())),
                'Post updated',
            );
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to update forum post', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/forum/posts/{post}',
        tags: ['Forum'],
        summary: 'Eliminar post',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Post eliminado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function destroy(Request $request, ForumPost $post)
    {
        try {
            $this->service->deletePost($request->user(), $post);

            return $this->successResponse(null, 'Post deleted');
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete forum post', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/forum/posts/{post}/comments',
        tags: ['Forum'],
        summary: 'Agregar comentario a un post',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreForumCommentRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Comentario creado'),
        ],
    )]
    public function addComment(StoreForumCommentRequest $request, ForumPost $post)
    {
        try {
            $comment = $this->service->createComment($request->user(), $post, $request->validated('content'));

            return $this->successResponse(new ForumCommentResource($comment), 'Comment created', 201);
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to create comment', 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/forum/comments/{comment}',
        tags: ['Forum'],
        summary: 'Eliminar comentario',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comentario eliminado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ],
    )]
    public function deleteComment(Request $request, ForumComment $comment)
    {
        try {
            $this->service->deleteComment($request->user(), $comment);

            return $this->successResponse(null, 'Comment deleted');
        } catch (HttpException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to delete comment', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/forum/posts/{post}/reactions',
        tags: ['Forum'],
        summary: 'Toggle reacción en un post',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreForumReactionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Reacción toggleada'),
        ],
    )]
    public function togglePostReaction(StoreForumReactionRequest $request, ForumPost $post)
    {
        try {
            $added = $this->service->toggleReaction($request->user(), $post, $request->validated('type'));

            return $this->successResponse(['added' => $added], $added ? 'Reaction added' : 'Reaction removed');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to toggle reaction', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/forum/comments/{comment}/reactions',
        tags: ['Forum'],
        summary: 'Toggle reacción en un comentario',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreForumReactionRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Reacción toggleada'),
        ],
    )]
    public function toggleCommentReaction(StoreForumReactionRequest $request, ForumComment $comment)
    {
        try {
            $added = $this->service->toggleReaction($request->user(), $comment, $request->validated('type'));

            return $this->successResponse(['added' => $added], $added ? 'Reaction added' : 'Reaction removed');
        } catch (Exception $e) {
            report($e);

            return $this->errorResponse('Failed to toggle reaction', 500);
        }
    }
}
