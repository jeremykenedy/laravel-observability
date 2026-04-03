<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Jeremykenedy\LaravelObservability\Providers\ObservabilityServiceProvider;

// ========================================================================
// Health endpoint
// ========================================================================

it('health endpoint returns 200', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['status', 'checks', 'timestamp']);
});

it('health endpoint returns check results', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $response = $this->getJson('/health')->assertOk();
    $data = $response->json();
    expect($data['status'])->toBeIn(['healthy', 'degraded']);
    expect($data['checks'])->toBeArray();
});

it('health endpoint includes database check', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['database' => ['status', 'message']]]);
});

it('health endpoint includes cache check', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['cache' => ['status', 'message']]]);
});

it('health endpoint includes storage check', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['storage' => ['status', 'message']]]);
});

it('health endpoint includes queue check', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['queue' => ['status', 'message']]]);
});

// ========================================================================
// Providers endpoint
// ========================================================================

it('providers endpoint returns provider lists', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $this->getJson('/health/providers')
        ->assertOk()
        ->assertJsonStructure(['detected', 'active', 'backend', 'frontend', 'testing', 'uptime']);
});

it('providers endpoint detected is array', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $response = $this->getJson('/health/providers')->assertOk();
    expect($response->json('detected'))->toBeArray();
});

it('providers endpoint active is array', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    $response = $this->getJson('/health/providers')->assertOk();
    expect($response->json('active'))->toBeArray();
});

// ========================================================================
// CSS frameworks
// ========================================================================

it('health endpoint works across all css frameworks', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    foreach (['tailwind', 'bootstrap5', 'bootstrap4'] as $fw) {
        config(['ui-kit.css_framework' => $fw]);
        $this->getJson('/health')->assertOk();
    }
});

it('providers endpoint works across all css frameworks', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    foreach (['tailwind', 'bootstrap5', 'bootstrap4'] as $fw) {
        config(['ui-kit.css_framework' => $fw]);
        $this->getJson('/health/providers')->assertOk();
    }
});

// ========================================================================
// Frontend frameworks
// ========================================================================

it('health endpoint works across all frontend frameworks', function () {
    config(['observability.health.enabled' => true]);
    app()->make(ObservabilityServiceProvider::class, ['app' => app()])->boot();

    foreach (['blade', 'livewire', 'vue', 'react', 'svelte'] as $fe) {
        config(['ui-kit.frontend' => $fe]);
        $this->getJson('/health')->assertOk();
    }
});

// ========================================================================
// Blade directive
// ========================================================================

it('observabilityScripts blade directive exists', function () {
    $compiled = Blade::compileString('@observabilityScripts');
    expect($compiled)->toContain('getFrontendSnippets');
});

// ========================================================================
// Install command
// ========================================================================

it('observability install command runs successfully with force', function () {
    $this->artisan('observability:install', [
        '--css'      => 'tailwind',
        '--frontend' => 'blade',
        '--force'    => true,
    ])->assertSuccessful();
});
