<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Jeremykenedy\LaravelObservability\Console\InstallCommand;
use Jeremykenedy\LaravelObservability\Console\SwitchCommand;
use Jeremykenedy\LaravelObservability\Console\UpdateCommand;
use Jeremykenedy\LaravelObservability\Health\HealthChecker;
use Jeremykenedy\LaravelObservability\Livewire\HealthDashboard;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;
use Jeremykenedy\LaravelObservability\Services\UptimeService;
use Jeremykenedy\LaravelObservability\Support\FrameworkSettings;
use Livewire\Livewire;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/observability.php', 'observability');
        $this->app->singleton(ProviderDetector::class);
        $this->app->singleton(HealthChecker::class);
        $this->app->singleton(UptimeService::class);
    }

    public function boot(): void
    {
        $frameworks = $this->app->make(FrameworkSettings::class);
        $views = __DIR__.'/../../resources/views';
        $this->loadViewsFrom([
            resource_path('views/vendor/observability/'.$frameworks->css().'/blade'),
            $views.'/'.$frameworks->css().'/blade',
            $views,
        ], 'observability');
        Blade::anonymousComponentPath($views.'/components', 'observability');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'observability');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UpdateCommand::class,
                SwitchCommand::class,
            ]);
            $this->publishes([
                __DIR__.'/../../config/observability.php' => config_path('observability.php'),
            ], 'observability-config');
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/observability'),
            ], 'observability-views');

            foreach (['vue', 'react', 'svelte'] as $frontend) {
                $this->publishes([
                    __DIR__.'/../../resources/js/'.$frontend.'/pages' => resource_path('js/Pages/Observability'),
                    __DIR__.'/../../resources/js/shared/observability.js' => resource_path('js/shared/observability.js'),
                    __DIR__.'/../../resources/js/shared/observability.css' => resource_path('js/shared/observability.css'),
                ], 'observability-'.$frontend);
            }
        }

        $this->loadRoutesFrom(__DIR__.'/../../routes/assets.php');

        if (config('observability.enabled', true) && config('observability.health.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        }

        if (class_exists(Livewire::class)) {
            Livewire::component('health-dashboard', HealthDashboard::class);
        }

        $detector = $this->app->make(ProviderDetector::class);
        $detector->detect();

        Blade::directive('observabilityScripts', function () {
            return '<?php
                $__detector = app(\Jeremykenedy\LaravelObservability\Services\ProviderDetector::class);
                $__snippets = $__detector->getFrontendSnippets();
                if (! empty($__snippets)) {
                    echo "<script>\n";
                    foreach ($__snippets as $__name => $__code) {
                        echo "/* " . $__name . " */\n" . $__code . "\n";
                    }
                    echo "</script>\n";
                }
            ?>';
        });
    }
}
