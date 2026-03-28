<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Tests;

use Jeremykenedy\LaravelObservability\Providers\ObservabilityServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ObservabilityServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('observability.enabled', true);
        $app['config']->set('observability.health.enabled', false);
    }
}
