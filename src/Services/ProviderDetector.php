<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Services;

class ProviderDetector
{
    protected array $detected = [];

    protected array $classMap = [
        'sentry'       => 'Sentry\Laravel\ServiceProvider',
        'bugsnag'      => 'Bugsnag\BugsnagLaravel\BugsnagServiceProvider',
        'flare'        => 'Spatie\LaravelIgnition\IgnitionServiceProvider',
        'rollbar'      => 'Rollbar\Laravel\RollbarServiceProvider',
        'honeybadger'  => 'Honeybadger\HoneybadgerLaravel\HoneybadgerServiceProvider',
        'appsignal'    => 'Appsignal\Laravel\ServiceProvider',
        'link_checker' => 'Spatie\LinkChecker\LinkCheckerServiceProvider',
    ];

    protected array $credentialKeys = [
        'sentry'          => 'dsn',
        'bugsnag'         => 'api_key',
        'flare'           => 'key',
        'rollbar'         => 'access_token',
        'raygun'          => 'api_key',
        'honeybadger'     => 'api_key',
        'airbrake'        => 'project_key',
        'new_relic'       => 'license_key',
        'datadog'         => 'api_key',
        'appsignal'       => 'push_api_key',
        'loggly'          => 'token',
        'logrocket'       => 'app_id',
        'instabug'        => 'token',
        'gleap'           => 'api_key',
        'ghost_inspector' => 'api_key',
        'memfault'        => 'project_key',
    ];

    public function detect(): array
    {
        $this->detected = [];
        $providers = config('observability.providers', []);

        foreach ($providers as $name => $config) {
            if ($this->isAvailable($name, $config)) {
                $this->detected[] = $name;
            }
        }

        return $this->detected;
    }

    public function getDetected(): array
    {
        return $this->detected;
    }

    public function isActive(string $provider): bool
    {
        return in_array($provider, $this->detected)
            && config("observability.providers.{$provider}.enabled", false);
    }

    public function getActiveProviders(): array
    {
        return array_filter($this->detected, fn ($p) => $this->isActive($p));
    }

    public function getProvidersByType(string $type): array
    {
        $providers = config('observability.providers', []);

        return array_keys(array_filter($providers, function ($config) use ($type) {
            return ($config['type'] ?? 'backend') === $type && ($config['enabled'] ?? false);
        }));
    }

    public function getFrontendSnippets(): array
    {
        $snippets = [];
        $providers = config('observability.providers', []);

        foreach ($providers as $name => $config) {
            if (!($config['enabled'] ?? false)) {
                continue;
            }
            if (!in_array($config['type'] ?? 'backend', ['frontend', 'both'])) {
                continue;
            }
            if (isset($config['js_snippet'])) {
                $snippet = $config['js_snippet'];
                foreach ($config as $key => $value) {
                    if (is_string($value)) {
                        $snippet = str_replace('{'.$key.'}', $value, $snippet);
                    }
                }
                $snippets[$name] = $snippet;
            }
        }

        return $snippets;
    }

    public function getUptimeProviders(): array
    {
        return array_filter(
            config('observability.uptime', []),
            fn ($config) => $config['enabled'] ?? false
        );
    }

    protected function isAvailable(string $name, array $config): bool
    {
        if (isset($this->classMap[$name]) && !class_exists($this->classMap[$name])) {
            return false;
        }

        $credKey = $this->credentialKeys[$name] ?? null;
        if ($credKey && empty($config[$credKey] ?? null)) {
            return false;
        }

        if (in_array($name, ['lighthouse', 'ssl_checker', 'visual_tests', 'link_checker', 'crashlytics'])) {
            return true;
        }

        return true;
    }
}
