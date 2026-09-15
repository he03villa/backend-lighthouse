<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiExplainRequest;
use App\Http\Requests\AiGenerateDraftRequest;
use App\Http\Requests\AiSuggestRequest;
use App\Http\Requests\AiSummarizeRequest;
use App\Models\Activity;
use App\Models\Participant;
use App\Models\Program;
use App\Services\AiService;
use App\Tenancy\TenantContext;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AiService $aiService,
        protected TenantContext $tenantContext,
    ) {}

    #[OA\Post(
        path: '/api/v1/ai/summarize-progress',
        tags: ['AI'],
        summary: 'Resumir progreso de un participante',
        description: 'Genera un resumen del progreso educativo de un participante usando IA. Requiere permiso use_ai_assistant.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object',
            required: ['participant_id'],
            properties: [
                new OA\Property(property: 'participant_id', type: 'string', format: 'uuid'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Resumen generado', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'summary', type: 'string'),
                    ]),
                ],
            )),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 503, description: 'Servicio de IA no disponible'),
        ],
    )]
    public function summarizeProgress(AiSummarizeRequest $request): JsonResponse
    {
        try {
            $participant = Participant::findOrFail($request->validated('participant_id'));
            $summary = $this->aiService->summarizeProgress(
                $this->tenantContext->current(),
                $participant,
            );

            return $this->successResponse(['summary' => $summary]);
        } catch (Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }

            report($e);

            return $this->errorResponse('Failed to generate summary', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/ai/suggest-activities',
        tags: ['AI'],
        summary: 'Sugerir actividades similares',
        description: 'Genera sugerencias de actividades complementarias basadas en una actividad existente.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object',
            required: ['activity_id'],
            properties: [
                new OA\Property(property: 'activity_id', type: 'string', format: 'uuid'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Sugerencias generadas'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 503, description: 'Servicio de IA no disponible'),
        ],
    )]
    public function suggestActivities(AiSuggestRequest $request): JsonResponse
    {
        try {
            $activity = Activity::with('module')->findOrFail($request->validated('activity_id'));
            $program = Program::findOrFail($activity->module->program_id);

            $suggestions = $this->aiService->suggestActivities($program, $activity);

            return $this->successResponse(['suggestions' => $suggestions]);
        } catch (Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }

            report($e);

            return $this->errorResponse('Failed to generate suggestions', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/ai/explain-activity',
        tags: ['AI'],
        summary: 'Explicar actividad en lenguaje sencillo',
        description: 'Genera una explicación de una actividad en lenguaje simple para familias.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object',
            required: ['activity_id'],
            properties: [
                new OA\Property(property: 'activity_id', type: 'string', format: 'uuid'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Explicación generada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 503, description: 'Servicio de IA no disponible'),
        ],
    )]
    public function explainActivity(AiExplainRequest $request): JsonResponse
    {
        try {
            $activity = Activity::findOrFail($request->validated('activity_id'));
            $explanation = $this->aiService->explainActivity($activity);

            return $this->successResponse(['explanation' => $explanation]);
        } catch (Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }

            report($e);

            return $this->errorResponse('Failed to generate explanation', 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/ai/generate-draft',
        tags: ['AI'],
        summary: 'Generar borrador de meta o actividad',
        description: 'Genera un borrador de meta o actividad educativa usando IA.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            type: 'object',
            required: ['type'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['goal', 'activity']),
                new OA\Property(property: 'context', type: 'object', description: 'Contexto adicional para la generación'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Borrador generado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 503, description: 'Servicio de IA no disponible'),
        ],
    )]
    public function generateDraft(AiGenerateDraftRequest $request): JsonResponse
    {
        try {
            $draft = $this->aiService->generateDraft(
                $this->tenantContext->current(),
                $request->validated('type'),
                $request->validated('context', []),
            );

            return $this->successResponse(['draft' => $draft]);
        } catch (Exception $e) {
            if ($e instanceof HttpException) {
                throw $e;
            }

            report($e);

            return $this->errorResponse('Failed to generate draft', 500);
        }
    }
}
