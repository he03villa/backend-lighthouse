<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $allHealthy = true;

        try {
            DB::select('SELECT 1');
            $checks['database'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        try {
            $start = microtime(true);
            Cache::put('health_check', true, 5);
            $cached = Cache::get('health_check');
            $duration = round((microtime(true) - $start) * 1000, 2);

            $checks['cache'] = [
                'status' => $cached ? 'ok' : 'error',
                'latency_ms' => $duration,
            ];
            if (! $cached) {
                $allHealthy = false;
            }
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        try {
            $jobsTableExists = DB::getSchemaBuilder()->hasTable('jobs');
            $checks['queue'] = [
                'status' => $jobsTableExists ? 'ok' : 'error',
                'connection' => config('queue.default'),
                'message' => $jobsTableExists ? 'Jobs table exists' : 'Jobs table missing',
            ];
            if (! $jobsTableExists) {
                $allHealthy = false;
            }
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'error', 'message' => $e->getMessage()];
            $allHealthy = false;
        }

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }
}
