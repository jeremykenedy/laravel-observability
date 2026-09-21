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
            $response = Http::asForm()->connectTimeout(3)->timeout(10)->retry(2, 200, throw: false)->post('https://api.uptimerobot.com/v2/getMonitors', [
                'api_key' => $apiKey,
                'format'  => 'json',
            ]);

            if ($response->ok()) {
                $monitors = $response->json('monitors', []);

                return is_array($monitors) ? $monitors : null;
            }
        } catch (\Throwable) {
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
            $response = Http::connectTimeout(3)->timeout(10)->retry(2, 200, throw: false)->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->get('https://api.statuscake.com/v1/uptime');

            if ($response->ok()) {
                $data = $response->json('data', []);

                return is_array($data) ? $data : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
