<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Services;

use Illuminate\Support\Facades\Http;

class UptimeService
{
    public function getUptimeRobotStatus(): ?array
    {
        $apiKey = config('observability.uptime.uptimerobot.api_key');

        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::post('https://api.uptimerobot.com/v2/getMonitors', [
                'api_key' => $apiKey,
                'format'  => 'json',
            ]);

            if ($response->ok()) {
                return $response->json('monitors', []);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function getStatusCakeStatus(): ?array
    {
        $apiKey = config('observability.uptime.statuscake.api_key');

        if (!$apiKey) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->get('https://api.statuscake.com/v1/uptime');

            if ($response->ok()) {
                return $response->json('data', []);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
