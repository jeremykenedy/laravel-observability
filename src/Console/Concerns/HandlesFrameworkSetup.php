<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console\Concerns;

use Jeremykenedy\LaravelObservability\Support\EnvironmentFile;
use Jeremykenedy\LaravelObservability\Support\FrameworkSettings;

trait HandlesFrameworkSetup
{
    public function __construct(protected EnvironmentFile $environment, protected FrameworkSettings $frameworks)
    {
        parent::__construct();
    }

    protected function getCssOption(): string
    {
        return $this->option('css') ?? $this->frameworks->css();
    }

    protected function getFrontendOption(): string
    {
        return $this->option('frontend') ?? $this->frameworks->frontend();
    }

    protected function validateFrameworks(): bool
    {
        if ($this->hasOption('ui-kit') && $this->option('ui-kit')) {
            if (!config()->has('ui-kit.css_framework')) {
                $this->error('Laravel UI Kit is optional and is not configured. Install jeremykenedy/laravel-ui-kit first, or omit --ui-kit.');

                return false;
            }

            foreach (['css' => 'css_framework', 'frontend' => 'frontend'] as $option => $key) {
                if ($this->option($option) === null) {
                    $this->input->setOption($option, config('ui-kit.'.$key, $option === 'css' ? 'tailwind' : 'blade'));
                }
            }
        }

        foreach (['css' => FrameworkSettings::CSS, 'frontend' => FrameworkSettings::FRONTENDS] as $option => $values) {
            $value = $this->option($option);
            if ($value !== null && !in_array($value, $values, true)) {
                $this->error("Invalid {$option}: {$value}. Use: ".implode(', ', $values));

                return false;
            }
        }

        return true;
    }

    protected function canSaveFrameworks(): bool
    {
        if (!$this->environment->exists()) {
            $this->error('A writable environment file is required. Create it before running this command.');

            return false;
        }

        return true;
    }

    protected function updateEnvValue(string $key, string $value): void
    {
        $this->environment->update([$key => $value]);
    }

    protected function setCssFramework(string $css): void
    {
        $this->saveFrameworks($css, $this->frameworks->frontend());
    }

    protected function setFrontendFramework(string $frontend): void
    {
        $this->saveFrameworks($this->frameworks->css(), $frontend);
    }

    protected function saveFrameworks(string $css, string $frontend): void
    {
        $this->environment->update(['OBSERVABILITY_CSS' => $css, 'OBSERVABILITY_FRONTEND' => $frontend]);
        config(['observability.css_framework' => $css, 'observability.frontend' => $frontend]);
        $this->callSilent('config:clear');
        $this->callSilent('view:clear');
        $this->publishFrontend($frontend);
    }

    protected function publishFrontend(string $frontend): void
    {
        if (in_array($frontend, ['vue', 'react', 'svelte'], true)) {
            $this->call('vendor:publish', ['--tag' => 'observability-'.$frontend]);
        }
    }
}
