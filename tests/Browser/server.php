<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Jeremykenedy\LaravelObservability\Providers\ObservabilityServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Foundation\Application;

require __DIR__.'/../../vendor/autoload.php';

$app = Application::create(options: ['extra' => ['providers' => [LivewireServiceProvider::class], 'dont-discover' => ['*']], 'load_environment_variables' => false]);
$app['config']->set([
    'app.key'                      => 'base64:'.base64_encode(str_repeat('b', 32)),
    'session.driver'               => 'array',
    'database.default'             => 'testing',
    'database.connections.testing' => ['driver' => 'sqlite', 'database' => ':memory:'],
    'cache.default'                => 'array',
    'view.paths'                   => [__DIR__.'/../fixtures/views'],
    'observability.health.route'   => '/status/check',
    'observability.css_framework'  => $_GET['css'] ?? 'tailwind',
]);
$app->register(ObservabilityServiceProvider::class);
$app['auth']->setUser(new GenericUser(['id' => 1, 'name' => 'Test User']));
Route::view('/livewire-dashboard', 'livewire-page');
$app['router']->getRoutes()->refreshNameLookups();
$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
