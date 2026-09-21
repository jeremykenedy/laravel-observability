<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Jeremykenedy\LaravelObservability\Health\HealthChecker;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;
use Jeremykenedy\LaravelObservability\Services\UptimeService;

class HealthController extends Controller
{
    public function __invoke(HealthChecker $checker): JsonResponse
    {
        $result = $checker->run();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    public function dashboard(HealthChecker $checker, ProviderDetector $detector): View
    {
        return view('observability::dashboard', [
            'healthData'   => $checker->run(),
            'providerData' => $detector->summary(),
        ]);
    }

    public function providers(ProviderDetector $detector): JsonResponse
    {
        return response()->json($detector->summary());
    }

    public function uptime(UptimeService $uptime): JsonResponse
    {
        $data = [];

        if (config('observability.uptime.uptimerobot.enabled')) {
            $data['uptimerobot'] = $uptime->getUptimeRobotStatus();
        }

        if (config('observability.uptime.statuscake.enabled')) {
            $data['statuscake'] = $uptime->getStatusCakeStatus();
        }

        return response()->json($data);
    }
}
