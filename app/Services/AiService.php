<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class AiService
{
    private const MODEL = 'openai/gpt-oss-20b';

    public function summarizeProgress(Tenant $tenant, Participant $participant): string
    {
        $progressRecords = $participant->progressRecords()->where('tenant_id', $tenant->id)->limit(10)->get();
        $enrollments = $participant->enrollments()->with('program')->where('tenant_id', $tenant->id)->get();
        $recentSubmissions = $participant->submissions()->latest('submitted_at')->limit(5)->get();

        $context = "Resumen de progreso del participante {$participant->first_name} {$participant->last_name}.\n\n";
        $context .= "Matrículas: {$enrollments->count()} programa(s).\n";
        foreach ($enrollments as $e) {
            $context .= "- {$e->program?->name}: {$e->status->value}\n";
        }

        $context .= "\nÚltimas entregas: {$recentSubmissions->count()}\n";
        foreach ($recentSubmissions as $s) {
            $context .= "- Actividad {$s->activity_id}: {$s->status->value}\n";
        }

        $context .= "\nRegistros de progreso: {$progressRecords->count()}\n";
        foreach ($progressRecords as $p) {
            $context .= "- Progreso {$p->percentage}% ({$p->completed_activities}/{$p->total_activities} actividades)\n";
        }

        return $this->chat(
            "Eres un asistente educativo. Resume el progreso del siguiente participante de forma clara y concisa. No hagas diagnósticos clínicos ni alertas médicas. Concéntrate en logros educativos y áreas de oportunidad.",
            $context,
        );
    }

    public function suggestActivities(Program $program, Activity $activity): array
    {
        $module = $activity->module;
        $siblingActivities = $module?->activities()->where('id', '!=', $activity->id)->get();

        $context = "Programa: {$program->name}\n";
        $context .= "Módulo: {$module?->name}\n";
        $context .= "Actividad actual: {$activity->name}\n";
        $context .= "Descripción: {$activity->description}\n";
        $context .= "Objetivo: {$activity->objective}\n";
        if ($siblingActivities) {
            $context .= "\nOtras actividades del módulo:\n";
            foreach ($siblingActivities as $s) {
                $context .= "- {$s->name}: {$s->description}\n";
            }
        }

        $response = $this->chat(
            "Eres un asistente educativo. Sugiere 3 actividades complementarias similares a la actividad descrita. Para cada una, responde con un JSON array de objetos con campos: name, description, objective. No hagas diagnósticos. Solo suggestions educativas.",
            $context,
        );

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : [['name' => 'Sugerencia', 'description' => $response, 'objective' => 'Práctica adicional']];
    }

    public function explainActivity(Activity $activity): string
    {
        $context = "Actividad: {$activity->name}\n";
        $context .= "Descripción: {$activity->description}\n";
        $context .= "Objetivo: {$activity->objective}\n";
        $context .= "Tipo: {$activity->type?->value}\n";
        $context .= "Nivel: {$activity->level}\n";

        return $this->chat(
            "Eres un asistente educativo. Explica la siguiente actividad en lenguaje sencillo para padres/familias. Sé breve (2-3 párrafos máximo). Usa un tono cálido y profesional. No uses jerga técnica ni diagnósticos.",
            $context,
        );
    }

    public function generateDraft(Tenant $tenant, string $type, array $context): array
    {
        $prompt = match ($type) {
            'goal' => "Eres un asistente educativo. Genera un borrador de meta educativa basado en el siguiente contexto. Responde con JSON: {\"title\": \"...\", \"description\": \"...\", \"target_date\": \"YYYY-MM-DD\"}. Contexto: " . json_encode($context),
            'activity' => "Eres un asistente educativo. Genera un borrador de actividad educativa. Responde con JSON: {\"name\": \"...\", \"description\": \"...\", \"objective\": \"...\", \"level\": \"...\"}. Contexto: " . json_encode($context),
            default => abort(422, 'Invalid draft type. Must be "goal" or "activity".'),
        };

        $response = $this->chat('No hagas diagnósticos clínicos.', $prompt);

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            return ['raw' => $response];
        }

        return $decoded;
    }

    protected function chat(string $systemPrompt, string $userMessage): string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = OpenAI::chat()->create([
                    'model' => self::MODEL,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                ]);

                return $response->choices[0]->message->content;
            } catch (Throwable $e) {
                $lastException = $e;
                Log::warning("AI service attempt {$attempt} failed: {$e->getMessage()}");

                if ($attempt < 2) {
                    usleep(500_000);
                }
            }
        }

        Log::error('AI service failed after 2 attempts', [
            'exception' => $lastException,
        ]);

        abort(503, 'AI service temporarily unavailable');
    }
}
