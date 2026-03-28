<?php

use Jeremykenedy\LaravelObservability\Health\HealthChecker;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;
use Jeremykenedy\LaravelObservability\Services\UptimeService;

// ========================================================================
// ServiceProvider & Container
// ========================================================================

it('resolves provider detector from container', function () {
    expect(app(ProviderDetector::class))->toBeInstanceOf(ProviderDetector::class);
});

it('resolves health checker from container', function () {
    expect(app(HealthChecker::class))->toBeInstanceOf(HealthChecker::class);
});

it('resolves uptime service from container', function () {
    expect(app(UptimeService::class))->toBeInstanceOf(UptimeService::class);
});

it('config loads all defaults', function () {
    expect(config('observability.enabled'))->toBeTrue();
    expect(config('observability.health.route'))->toBe('/health');
    expect(config('observability.health.checks'))->toContain('database');
    expect(config('observability.health.checks'))->toContain('cache');
    expect(config('observability.health.checks'))->toContain('storage');
    expect(config('observability.health.checks'))->toContain('queue');
    expect(config('observability.providers'))->toBeArray();
    expect(config('observability.uptime'))->toBeArray();
    expect(config('observability.context'))->toBeArray();
});

it('config has all provider entries', function () {
    $expected = [
        'sentry', 'bugsnag', 'flare', 'rollbar', 'raygun', 'honeybadger',
        'airbrake', 'new_relic', 'datadog', 'appsignal', 'loggly',
        'logrocket', 'instabug', 'gleap', 'crashlytics', 'memfault',
        'ghost_inspector', 'lighthouse', 'link_checker', 'ssl_checker', 'visual_tests',
    ];

    foreach ($expected as $name) {
        expect(config('observability.providers'))->toHaveKey($name);
    }
});

it('config has uptime providers', function () {
    expect(config('observability.uptime'))->toHaveKey('uptimerobot');
    expect(config('observability.uptime'))->toHaveKey('statuscake');
});

it('each provider config has type field', function () {
    foreach (config('observability.providers') as $name => $cfg) {
        expect($cfg)->toHaveKey('type', "Provider {$name} missing type");
        expect($cfg['type'])->toBeIn(['backend', 'frontend', 'both', 'testing']);
    }
});

it('each provider config has docs field', function () {
    foreach (config('observability.providers') as $name => $cfg) {
        expect($cfg)->toHaveKey('docs', "Provider {$name} missing docs");
    }
});

// ========================================================================
// ProviderDetector: detection
// ========================================================================

it('detect returns array', function () {
    expect((new ProviderDetector())->detect())->toBeArray();
});

it('detects testing providers without credentials', function () {
    $detected = (new ProviderDetector())->detect();
    expect($detected)->toContain('lighthouse');
    expect($detected)->toContain('ssl_checker');
    expect($detected)->toContain('visual_tests');
});

it('does not detect credential-based providers without credentials', function () {
    config(['observability.providers.sentry.dsn' => null]);
    config(['observability.providers.bugsnag.api_key' => null]);
    $detected = (new ProviderDetector())->detect();
    expect($detected)->not->toContain('sentry');
    expect($detected)->not->toContain('bugsnag');
});

it('getDetected returns empty before detect called', function () {
    expect((new ProviderDetector())->getDetected())->toBeEmpty();
});

// ========================================================================
// ProviderDetector: isActive
// ========================================================================

it('isActive returns false for unconfigured providers', function () {
    $d = new ProviderDetector();
    $d->detect();
    expect($d->isActive('sentry'))->toBeFalse();
    expect($d->isActive('nonexistent'))->toBeFalse();
});

it('isActive returns true for enabled and detected provider', function () {
    config(['observability.providers.lighthouse.enabled' => true]);
    $d = new ProviderDetector();
    $d->detect();
    expect($d->isActive('lighthouse'))->toBeTrue();
});

// ========================================================================
// ProviderDetector: getActiveProviders
// ========================================================================

it('getActiveProviders filters to only active', function () {
    config(['observability.providers.lighthouse.enabled' => true]);
    config(['observability.providers.ssl_checker.enabled' => false]);
    $d = new ProviderDetector();
    $d->detect();
    $active = $d->getActiveProviders();
    expect($active)->toContain('lighthouse');
    expect($active)->not->toContain('ssl_checker');
});

// ========================================================================
// ProviderDetector: getProvidersByType
// ========================================================================

it('getProvidersByType returns correct types', function () {
    config(['observability.providers.logrocket.enabled' => true]);
    config(['observability.providers.logrocket.app_id' => 'test']);
    $d = new ProviderDetector();
    $d->detect();
    expect($d->getProvidersByType('frontend'))->toContain('logrocket');
    expect($d->getProvidersByType('backend'))->not->toContain('logrocket');
});

it('getProvidersByType returns empty for nonexistent type', function () {
    $d = new ProviderDetector();
    $d->detect();
    expect($d->getProvidersByType('nonexistent'))->toBeEmpty();
});

// ========================================================================
// ProviderDetector: getFrontendSnippets
// ========================================================================

it('getFrontendSnippets returns empty when nothing enabled', function () {
    expect((new ProviderDetector())->getFrontendSnippets())->toBeEmpty();
});

it('getFrontendSnippets returns snippet with credentials injected', function () {
    config(['observability.providers.logrocket.enabled' => true]);
    config(['observability.providers.logrocket.app_id' => 'my-app-123']);
    $d = new ProviderDetector();
    $d->detect();
    $snippets = $d->getFrontendSnippets();
    expect($snippets)->toHaveKey('logrocket');
    expect($snippets['logrocket'])->toContain('my-app-123');
    expect($snippets['logrocket'])->not->toContain('{app_id}');
});

it('getFrontendSnippets excludes backend-only providers', function () {
    config(['observability.providers.sentry.enabled' => true]);
    config(['observability.providers.sentry.dsn' => 'test']);
    $d = new ProviderDetector();
    $d->detect();
    expect($d->getFrontendSnippets())->not->toHaveKey('sentry');
});

// ========================================================================
// ProviderDetector: getUptimeProviders
// ========================================================================

it('getUptimeProviders returns only enabled', function () {
    config(['observability.uptime.uptimerobot.enabled' => true]);
    config(['observability.uptime.statuscake.enabled' => false]);
    $d = new ProviderDetector();
    $result = $d->getUptimeProviders();
    expect($result)->toHaveKey('uptimerobot');
    expect($result)->not->toHaveKey('statuscake');
});

it('getUptimeProviders returns empty when none enabled', function () {
    config(['observability.uptime.uptimerobot.enabled' => false]);
    config(['observability.uptime.statuscake.enabled' => false]);
    expect((new ProviderDetector())->getUptimeProviders())->toBeEmpty();
});

// ========================================================================
// HealthChecker
// ========================================================================

it('health checker runs all checks', function () {
    config(['observability.health.checks' => ['database', 'cache', 'storage', 'queue']]);
    $result = (new HealthChecker())->run();
    expect($result)->toHaveKeys(['status', 'checks', 'timestamp']);
    expect($result['checks'])->toHaveKeys(['database', 'cache', 'storage', 'queue']);
});

it('health check status is ok or fail per check', function () {
    config(['observability.health.checks' => ['cache']]);
    $result = (new HealthChecker())->run();
    expect($result['checks']['cache']['status'])->toBeIn(['ok', 'fail']);
});

it('health check returns healthy or degraded', function () {
    $result = (new HealthChecker())->run();
    expect($result['status'])->toBeIn(['healthy', 'degraded']);
});

it('health check with empty checks returns healthy', function () {
    config(['observability.health.checks' => []]);
    $result = (new HealthChecker())->run();
    expect($result['status'])->toBe('healthy');
    expect($result['checks'])->toBeEmpty();
});

it('health check includes timestamp', function () {
    $result = (new HealthChecker())->run();
    expect($result['timestamp'])->toBeString();
});

// ========================================================================
// UptimeService
// ========================================================================

it('uptimerobot returns null without api key', function () {
    config(['observability.uptime.uptimerobot.api_key' => null]);
    expect((new UptimeService())->getUptimeRobotStatus())->toBeNull();
});

it('statuscake returns null without api key', function () {
    config(['observability.uptime.statuscake.api_key' => null]);
    expect((new UptimeService())->getStatusCakeStatus())->toBeNull();
});

// ========================================================================
// Install command
// ========================================================================

it('observability install command is registered', function () {
    expect(array_keys(\Illuminate\Support\Facades\Artisan::all()))->toContain('observability:install');
});
