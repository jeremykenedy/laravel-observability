<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\File;
use Jeremykenedy\LaravelObservability\Livewire\HealthDashboard;
use Livewire\Livewire;

it('renders each Blade framework using the original route and host layout', function (string $css) {
    config(['observability.css_framework' => $css]);
    $this->enableHealthRoutes();
    $this->actingAs(new GenericUser(['id' => 1]));
    $this->get('/health/dashboard')->assertOk()
        ->assertSee('data-css="'.$css.'"', false)
        ->assertSee('All systems operational')->assertSee('Appearance')
        ->assertSee(route('health.assets', ['asset' => 'blade.js']), false)
        ->assertSee('Database connection successful');
})->with(['tailwind', 'bootstrap5', 'bootstrap4']);

it('keeps dashboard and uptime routes authenticated', function () {
    $this->enableHealthRoutes();
    $this->getJson('/health/dashboard')->assertUnauthorized();
    $this->getJson('/health/uptime')->assertUnauthorized();
});

it('preserves root and framework-specific published views', function (string $path) {
    File::ensureDirectoryExists(dirname(resource_path('views/vendor/observability/'.$path)));
    File::put(resource_path('views/vendor/observability/'.$path), 'My existing dashboard');
    $this->enableHealthRoutes();
    $this->actingAs(new GenericUser(['id' => 1]));
    $this->get('/health/dashboard')->assertOk()->assertSee('My existing dashboard');
})->with(['dashboard.blade.php', 'tailwind/blade/dashboard.blade.php']);

it('uses the named health route for customized endpoints and keeps sibling URLs compatible', function () {
    config(['observability.health.route' => '/status/check']);
    $this->enableHealthRoutes();
    $this->actingAs(new GenericUser(['id' => 1]));
    $this->get('/health/dashboard')->assertOk()->assertSee('data-health-url="http://localhost/status/check"', false);
    $this->getJson('/status/check')->assertOk();
    $this->getJson('/health/providers')->assertOk();
});

it('serves only the dashboard asset allowlist', function (string $asset, string $type) {
    $this->enableHealthRoutes();
    $response = $this->get('/health/assets/'.$asset)->assertOk();
    expect(strtolower($response->headers->get('Content-Type')))->toBe($type);
    $this->get('/health/assets/observability.php')->assertNotFound();
})->with([['observability.css', 'text/css; charset=utf-8'], ['observability.js', 'text/javascript; charset=utf-8'], ['blade.js', 'text/javascript; charset=utf-8']]);

it('renders and refreshes Livewire without leaking backend exceptions', function (string $css) {
    config(['observability.css_framework' => $css]);
    $this->enableHealthRoutes();
    $component = Livewire::test(HealthDashboard::class)->assertSee('All systems operational')->assertSee('data-css="'.$css.'"', false);
    config(['observability.health.checks' => ['unknown']]);
    $component->call('refresh')->assertSet('healthData.status', 'degraded')->assertSee('Some checks need attention');
})->with(['tailwind', 'bootstrap5', 'bootstrap4'])->skip(!class_exists(Livewire::class), 'Livewire is not installed.');

it('does not register routes when globally disabled', function () {
    config(['observability.enabled' => false]);
    $this->enableHealthRoutes();
    $this->getJson('/health')->assertNotFound();
});

it('escapes provider names in the dashboard', function () {
    config(['observability.providers' => ['<script>alert(1)</script>' => ['enabled' => true, 'type' => 'backend']]]);
    $this->enableHealthRoutes();
    $this->actingAs(new GenericUser(['id' => 1]));
    $this->get('/health/dashboard')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('keeps standalone Livewire components usable with health routes disabled', function () {
    expect(config('observability.health.enabled'))->toBeFalse();
    Livewire::test(HealthDashboard::class)->assertSee('All systems operational')->call('refresh')->assertSet('healthData.status', 'healthy');
    $this->get('/health/assets/observability.css')->assertOk();
    $this->getJson('/health')->assertNotFound();
})->skip(!class_exists(Livewire::class), 'Livewire is not installed.');
