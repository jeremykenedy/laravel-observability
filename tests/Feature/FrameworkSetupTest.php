<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use Jeremykenedy\LaravelObservability\Support\EnvironmentFile;
use Jeremykenedy\LaravelObservability\Support\FrameworkSettings;

it('preserves the original defaults and existing UI Kit selection', function () {
    $settings = $this->app->make(FrameworkSettings::class);
    expect($settings->css())->toBe('tailwind')->and($settings->frontend())->toBe('blade');
    config(['ui-kit.css_framework' => 'bootstrap4', 'ui-kit.frontend' => 'vue']);
    expect($settings->css())->toBe('bootstrap4')->and($settings->frontend())->toBe('vue');
    config(['observability.css_framework' => 'bootstrap5', 'observability.frontend' => 'react']);
    expect($settings->css())->toBe('bootstrap5')->and($settings->frontend())->toBe('react');
});

it('falls back safely for invalid configured frameworks', function () {
    config(['observability.css_framework' => '../../invalid', 'observability.frontend' => 'invalid']);
    $settings = $this->app->make(FrameworkSettings::class);
    expect($settings->css())->toBe('tailwind')->and($settings->frontend())->toBe('blade');
});

it('installs every frontend and CSS choice without changing shared settings', function (string $css, string $frontend) {
    $this->artisan('observability:install', ['--css' => $css, '--frontend' => $frontend, '--no-interaction' => true])->assertSuccessful();
    $values = Dotenv::parse(File::get($this->app->environmentFilePath()));
    expect($values)->toMatchArray(['OBSERVABILITY_CSS' => $css, 'OBSERVABILITY_FRONTEND' => $frontend, 'UI_KIT_CSS' => 'tailwind']);
    expect(File::exists(config_path('observability.php')))->toBeTrue();
    if (in_array($frontend, ['vue', 'react', 'svelte'])) {
        $extension = ['vue' => 'vue', 'react' => 'jsx', 'svelte' => 'svelte'][$frontend];
        expect(File::exists(resource_path('js/Pages/Observability/HealthDashboard.'.$extension)))->toBeTrue();
        expect(File::exists(resource_path('js/shared/observability.js')))->toBeTrue();
        expect(File::exists(resource_path('js/shared/observability.css')))->toBeTrue();
    }
})->with((function () {
    foreach (FrameworkSettings::CSS as $css) {
        foreach (FrameworkSettings::FRONTENDS as $frontend) {
            yield $css.' '.$frontend => [$css, $frontend];
        }
    }
})());

it('rejects invalid options before changing any files', function (string $command, array $options) {
    $before = File::get($this->app->environmentFilePath());
    $this->artisan($command, $options + ['--no-interaction' => true])->assertFailed();
    expect(File::get($this->app->environmentFilePath()))->toBe($before);
    expect(File::exists(config_path('observability.php')))->toBeFalse();
})->with(['observability:install', 'observability:update', 'observability:switch'])->with([
    [['--css' => '../bad']], [['--frontend' => 'bad']], [['--css' => 'bootstrap5', '--frontend' => 'bad']], [['--css' => '']],
]);

it('refuses a noninteractive reinstall without force and preserves custom files with force', function () {
    File::put(config_path('observability.php'), '<?php return ["enabled" => false];');
    File::ensureDirectoryExists(resource_path('views/vendor/observability'));
    File::put(resource_path('views/vendor/observability/dashboard.blade.php'), 'Custom dashboard');
    $this->artisan('observability:install', ['--no-interaction' => true])->assertFailed();
    $this->artisan('observability:install', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
    expect(File::get(config_path('observability.php')))->toBe('<?php return ["enabled" => false];');
    expect(File::get(resource_path('views/vendor/observability/dashboard.blade.php')))->toBe('Custom dashboard');
});

it('updates frameworks without overwriting configuration or edited frontend files', function () {
    File::put(config_path('observability.php'), '<?php return ["providers" => ["custom" => []]];');
    File::ensureDirectoryExists(resource_path('js/Pages/Observability'));
    File::put(resource_path('js/Pages/Observability/HealthDashboard.vue'), 'Custom component');
    $this->artisan('observability:update', ['--css' => 'bootstrap4', '--frontend' => 'vue', '--no-interaction' => true])->assertSuccessful();
    expect(File::get(resource_path('js/Pages/Observability/HealthDashboard.vue')))->toBe('Custom component');
    expect(File::get(config_path('observability.php')))->toBe('<?php return ["providers" => ["custom" => []]];');
    $this->artisan('observability:update', ['--no-interaction' => true])->assertSuccessful();
    expect(config('observability.frontend'))->toBe('vue');
});

it('keeps the other framework when switching a single option', function () {
    config(['observability.frontend' => 'svelte']);
    $this->artisan('observability:switch', ['--css' => 'bootstrap5'])->assertSuccessful();
    expect(Dotenv::parse(File::get($this->app->environmentFilePath())))
        ->toMatchArray(['OBSERVABILITY_CSS' => 'bootstrap5', 'OBSERVABILITY_FRONTEND' => 'svelte']);
});

it('requires a flag for switch and an installation for update', function () {
    $this->artisan('observability:switch')->assertFailed();
    $this->artisan('observability:update', ['--no-interaction' => true])->assertFailed();
});

it('fails without a writable environment file instead of reporting a saved selection', function () {
    File::delete($this->app->environmentFilePath());
    $this->artisan('observability:install', ['--no-interaction' => true])->assertFailed();
    expect(File::exists(config_path('observability.php')))->toBeFalse();
});

it('uses UI Kit only when requested and keeps its configuration untouched', function () {
    $this->artisan('observability:install', ['--ui-kit' => true, '--no-interaction' => true])->assertFailed();
    config(['ui-kit.css_framework' => 'bootstrap4', 'ui-kit.frontend' => 'react']);
    $this->artisan('observability:install', ['--ui-kit' => true, '--css' => 'bootstrap5', '--no-interaction' => true])->assertSuccessful();
    expect(config('ui-kit.css_framework'))->toBe('bootstrap4');
    expect(config('observability.css_framework'))->toBe('bootstrap5');
    expect(config('observability.frontend'))->toBe('react');
});

it('writes literal environment values and honors a custom environment filename', function () {
    $this->app->loadEnvironmentFrom('.env.custom');
    $path = $this->app->environmentFilePath();
    File::put($path, "# TOKEN=comment\r\nOTHER_TOKEN=keep\r\nexport TOKEN = old\r\n");
    $value = 'secret $1 ${OTHER_TOKEN} # "quoted" \\ path';
    $this->app->make(EnvironmentFile::class)->update(['TOKEN' => $value]);
    expect(Dotenv::parse(File::get($path)))->toMatchArray(['TOKEN' => $value, 'OTHER_TOKEN' => 'keep']);
    expect(File::get($path))->toContain("# TOKEN=comment\r\n");
});

it('rejects multiline environment values without writing a partial update', function () {
    $before = File::get($this->app->environmentFilePath());
    expect(fn () => $this->app->make(EnvironmentFile::class)->update(['FIRST' => 'value', 'TOKEN' => "bad\nINJECTED=true"]))->toThrow(InvalidArgumentException::class);
    expect(File::get($this->app->environmentFilePath()))->toBe($before);
});

it('does not save invalid settings imported from UI Kit', function () {
    File::put(config_path('observability.php'), '<?php return [];');
    config(['ui-kit.css_framework' => 'invalid', 'ui-kit.frontend' => 'blade']);
    $before = File::get($this->app->environmentFilePath());
    $this->artisan('observability:update', ['--ui-kit' => true, '--no-interaction' => true])->assertFailed();
    expect(File::get($this->app->environmentFilePath()))->toBe($before);
});

it('allows interactive installation without optional monitoring providers', function () {
    $this->artisan('observability:install', ['--css' => 'tailwind', '--frontend' => 'blade'])
        ->expectsQuestion('Select backend providers to install:', [])
        ->expectsQuestion('Select APM providers:', [])
        ->expectsQuestion('Select frontend monitoring providers:', [])
        ->expectsQuestion('Select testing & uptime providers:', [])
        ->assertSuccessful();
    expect(File::exists(config_path('observability.php')))->toBeTrue();
});
