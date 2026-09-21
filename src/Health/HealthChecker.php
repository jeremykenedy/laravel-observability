<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HealthChecker
{
    public function run(): array
    {
        $checks = config('observability.health.checks', []);
        $results = [];

        foreach ($checks as $check) {
            $results[$check] = match ($check) {
                'database' => $this->checkDatabase(),
                'cache'    => $this->checkCache(),
                'storage'  => $this->checkStorage(),
                'queue'    => $this->checkQueue(),
                default    => ['status' => 'unknown', 'message' => 'Unknown check'],
            };
        }

        $healthy = collect($results)->every(fn ($r) => $r['status'] === 'ok');

        return [
            'status'    => $healthy ? 'healthy' : 'degraded',
            'checks'    => $results,
            'timestamp' => now()->toISOString(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Database check failed'];
        }
    }

    protected function checkCache(): array
    {
        $key = 'observability:health:'.Str::uuid();

        try {
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value ? ['status' => 'ok', 'message' => 'Cache working'] : ['status' => 'error', 'message' => 'Cache read failed'];
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Cache check failed'];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $path = 'observability-health-'.Str::uuid().'.txt';

            try {
                if (!$disk->put($path, 'ok') || $disk->get($path) !== 'ok') {
                    return ['status' => 'error', 'message' => 'Storage write or read failed'];
                }
            } finally {
                $deleted = $disk->delete($path);
            }

            if (!$deleted) {
                return ['status' => 'error', 'message' => 'Storage cleanup failed'];
            }

            return ['status' => 'ok', 'message' => 'Storage writable'];
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Storage check failed'];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default', 'sync');

            return ['status' => 'ok', 'message' => "Queue driver: {$connection}"];
        } catch (\Throwable) {
            return ['status' => 'error', 'message' => 'Queue check failed'];
        }
    }
}
