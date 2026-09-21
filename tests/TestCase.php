<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Jeremykenedy\LaravelObservability\Providers\ObservabilityServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $workspace;

    protected function getPackageProviders($app): array
    {
        return array_values(array_filter([ObservabilityServiceProvider::class, LivewireServiceProvider::class], class_exists(...)));
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->workspace = sys_get_temp_dir().'/observability-tests-'.bin2hex(random_bytes(8));
        $files = new Filesystem();
        $files->makeDirectory($this->workspace.'/config', 0755, true);
        $files->makeDirectory($this->workspace.'/resources/views/layouts', 0755, true);
        $files->put($this->workspace.'/.env', "APP_NAME=Example\nUI_KIT_CSS=tailwind\n");
        $files->copy(__DIR__.'/fixtures/views/layouts/app.blade.php', $this->workspace.'/resources/views/layouts/app.blade.php');
        $app->useEnvironmentPath($this->workspace);
        $app->useConfigPath($this->workspace.'/config');
        $bootstrapPath = $app->bootstrapPath();
        $storagePath = $app->storagePath();
        $files->copy($app->basePath('composer.json'), $this->workspace.'/composer.json');
        $app->setBasePath($this->workspace);
        $app->useBootstrapPath($bootstrapPath);
        $app->useStoragePath($storagePath);
        $app['config']->set('view.paths', [$this->workspace.'/resources/views']);
        $app['config']->set('observability.enabled', true);
        $app['config']->set('observability.health.enabled', false);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.cipher', 'AES-256-CBC');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem())->deleteDirectory($this->workspace);
    }

    protected function enableHealthRoutes(): void
    {
        config(['observability.health.enabled' => true]);
        $this->app['view']->replaceNamespace('observability', []);
        (new ObservabilityServiceProvider($this->app))->boot();
    }
}
