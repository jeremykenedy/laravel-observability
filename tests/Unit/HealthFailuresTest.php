<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Jeremykenedy\LaravelObservability\Health\HealthChecker;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;

it('does not overwrite application cache keys or files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('health_check.txt', 'Application data');
    Cache::put('health_check', 'Application data');
    config(['observability.health.checks' => ['cache', 'storage']]);
    expect($this->app->make(HealthChecker::class)->run()['status'])->toBe('healthy');
    expect(Cache::get('health_check'))->toBe('Application data');
    expect(Storage::disk('local')->get('health_check.txt'))->toBe('Application data');
    expect(Storage::disk('local')->allFiles())->toBe(['health_check.txt']);
});

it('reports failed storage writes even when the disk does not throw', function () {
    $disk = Mockery::mock();
    $disk->shouldReceive('put')->once()->andReturn(false);
    $disk->shouldReceive('delete')->once()->andReturn(true);
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);
    config(['observability.health.checks' => ['storage']]);
    expect($this->app->make(HealthChecker::class)->run())
        ->toMatchArray(['status' => 'degraded', 'checks' => ['storage' => ['status' => 'error', 'message' => 'Storage write or read failed']]]);
});

it('reports storage cleanup failures', function () {
    $disk = Mockery::mock();
    $disk->shouldReceive('put')->andReturn(true);
    $disk->shouldReceive('get')->andReturn('ok');
    $disk->shouldReceive('delete')->andReturn(false);
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);
    config(['observability.health.checks' => ['storage']]);
    expect($this->app->make(HealthChecker::class)->run()['checks']['storage']['status'])->toBe('error');
});

it('returns 503 without exposing database credentials', function () {
    DB::shouldReceive('connection')->andThrow(new RuntimeException('password=private host=internal'));
    config(['observability.health.checks' => ['database']]);
    $this->enableHealthRoutes();
    $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.database.status', 'error')->assertDontSee('private')->assertDontSee('internal');
});

it('returns degraded for an unknown check', function () {
    config(['observability.health.checks' => ['not-a-check']]);
    $this->enableHealthRoutes();
    $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.not-a-check.status', 'unknown');
});

it('returns active providers as a JSON list and includes both provider types', function () {
    config(['observability.providers' => [
        'disabled' => ['enabled' => false, 'type' => 'backend'],
        'shared'   => ['enabled' => true, 'type' => 'both'],
    ]]);
    $this->enableHealthRoutes();
    $this->getJson('/health/providers')->assertOk()->assertExactJson([
        'detected' => ['disabled', 'shared'], 'active' => ['shared'], 'backend' => ['shared'], 'frontend' => ['shared'], 'testing' => [], 'uptime' => [],
    ]);
});

it('escapes credentials embedded in frontend script strings', function () {
    config(['observability.providers' => ['example' => ['enabled' => true, 'type' => 'frontend', 'token' => "'</script><script>alert(1)</script>", 'js_snippet' => "window.example('{token}');"]]]);
    $snippet = $this->app->make(ProviderDetector::class)->getFrontendSnippets()['example'];
    expect($snippet)->not->toContain('</script>')->toContain('\\u0027')->toContain('\\u003C');
});
