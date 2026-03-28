<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthChecker
{
    public function run(): array
    {
        $checks = config('observability.health.checks', []);
        $results = [];

        foreach ($checks as $check) {
            $results[$check] = match ($check) {
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'queue' => $this->checkQueue(),
                default => ['status' => 'unknown', 'message' => 'Unknown check'],
            };
        }

        $healthy = collect($results)->every(fn ($r) => $r['status'] === 'ok');

        return [
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $results,
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Database: ' . $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            return $value ? ['status' => 'ok', 'message' => 'Cache working'] : ['status' => 'error', 'message' => 'Cache read failed'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Cache: ' . $e->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $disk->put('health_check.txt', 'ok');
            $disk->delete('health_check.txt');
            return ['status' => 'ok', 'message' => 'Storage writable'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Storage: ' . $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default', 'sync');
            return ['status' => 'ok', 'message' => "Queue driver: {$connection}"];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Queue: ' . $e->getMessage()];
        }
    }
}
