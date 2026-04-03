<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\BlockedItemsTableSeeder;
use Database\Seeders\BlockedTypeTableSeeder;
use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->seed(BlockedTypeTableSeeder::class);
    $this->seed(BlockedItemsTableSeeder::class);
    $this->seed(RolesTableSeeder::class);
    $this->seed(PermissionsTableSeeder::class);

    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->user->attachRole(Role::where('slug', 'user')->first());
});

// ========================================================================
// Health endpoint
// ========================================================================

it('health endpoint returns 200', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['status', 'checks', 'timestamp']);
});

it('health endpoint returns check results', function () {
    $response = $this->getJson('/health')->assertOk();
    $data = $response->json();
    expect($data['status'])->toBeIn(['healthy', 'degraded']);
    expect($data['checks'])->toBeArray();
});

it('health endpoint includes database check', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['database' => ['status', 'message']]]);
});

it('health endpoint includes cache check', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['cache' => ['status', 'message']]]);
});

it('health endpoint includes storage check', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['storage' => ['status', 'message']]]);
});

it('health endpoint includes queue check', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJsonStructure(['checks' => ['queue' => ['status', 'message']]]);
});

// ========================================================================
// Providers endpoint
// ========================================================================

it('providers endpoint returns provider lists', function () {
    $this->getJson('/health/providers')
        ->assertOk()
        ->assertJsonStructure(['detected', 'active', 'backend', 'frontend', 'testing', 'uptime']);
});

it('providers endpoint detected is array', function () {
    $response = $this->getJson('/health/providers')->assertOk();
    expect($response->json('detected'))->toBeArray();
});

it('providers endpoint active is array', function () {
    $response = $this->getJson('/health/providers')->assertOk();
    expect($response->json('active'))->toBeArray();
});

it('providers endpoint includes testing providers', function () {
    $response = $this->getJson('/health/providers')->assertOk();
    $detected = $response->json('detected');
    expect($detected)->toContain('lighthouse');
});

// ========================================================================
// Uptime endpoint
// ========================================================================

it('uptime endpoint requires auth', function () {
    $this->getJson('/health/uptime')
        ->assertUnauthorized();
});

it('uptime endpoint returns json for authed user', function () {
    $this->actingAs($this->user)
        ->getJson('/health/uptime')
        ->assertOk();
});

// ========================================================================
// All 3 CSS frameworks render health pages
// ========================================================================

it('health endpoint works across all css frameworks', function () {
    foreach (['tailwind', 'bootstrap5', 'bootstrap4'] as $fw) {
        config(['ui-kit.css_framework' => $fw]);
        $this->getJson('/health')->assertOk();
    }
});

it('providers endpoint works across all css frameworks', function () {
    foreach (['tailwind', 'bootstrap5', 'bootstrap4'] as $fw) {
        config(['ui-kit.css_framework' => $fw]);
        $this->getJson('/health/providers')->assertOk();
    }
});

// ========================================================================
// All 5 frontend frameworks
// ========================================================================

it('health endpoint works across all frontend frameworks', function () {
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

it('observability install command runs successfully', function () {
    $this->artisan('observability:install')->assertSuccessful();
});
