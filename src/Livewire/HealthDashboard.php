<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Livewire;

use Jeremykenedy\LaravelObservability\Health\HealthChecker;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;
use Livewire\Component;

class HealthDashboard extends Component
{
    public array $healthData = [];

    public array $providerData = [];

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $checker = app(HealthChecker::class);
        $this->healthData = $checker->run();

        $detector = app(ProviderDetector::class);
        $detector->detect();
        $this->providerData = [
            'active' => $detector->getActiveProviders(),
            'backend' => $detector->getProvidersByType('backend'),
            'frontend' => $detector->getProvidersByType('frontend'),
            'testing' => $detector->getProvidersByType('testing'),
        ];
    }

    public function render()
    {
        return view('observability::livewire.dashboard');
    }
}
