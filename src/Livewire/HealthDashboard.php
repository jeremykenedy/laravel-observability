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

    protected HealthChecker $checker;

    protected ProviderDetector $detector;

    public function boot(HealthChecker $checker, ProviderDetector $detector): void
    {
        $this->checker = $checker;
        $this->detector = $detector;
    }

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->healthData = $this->checker->run();
        $this->providerData = $this->detector->summary();
    }

    public function render()
    {
        return view('observability::livewire.dashboard', ['css' => config('observability.css_framework') ?? config('ui-kit.css_framework', 'tailwind')]);
    }
}
