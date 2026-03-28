<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Jeremykenedy\LaravelObservability\Health\HealthChecker;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;
use Jeremykenedy\LaravelObservability\Services\UptimeService;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/observability.php', 'observability');
        $this->app->singleton(ProviderDetector::class);
        $this->app->singleton(HealthChecker::class);
        $this->app->singleton(UptimeService::class);

        // Load CSS-framework-specific views
        $css = config('ui-kit.css_framework', 'tailwind');
        $viewPath = __DIR__.'/../../resources/views/'.$css.'/blade';
        if (! is_dir($viewPath)) {
            $viewPath = __DIR__.'/../../resources/views/tailwind/blade';
        }
        $this->loadViewsFrom([$viewPath, __DIR__.'/../../resources/views/'], 'observability');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Jeremykenedy\LaravelObservability\Console\InstallCommand::class,
                \Jeremykenedy\LaravelObservability\Console\UpdateCommand::class,
                \Jeremykenedy\LaravelObservability\Console\SwitchCommand::class,
            ]);
            $this->publishes([
                __DIR__.'/../../config/observability.php' => config_path('observability.php'),
            ], 'observability-config');
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/observability'),
            ], 'observability-views');

            $frontend = config('ui-kit.frontend', 'blade');
            if (! in_array($frontend, ['blade', 'livewire'])) {
                $jsPath = __DIR__.'/../../resources/js/'.$frontend.'/pages';
                if (is_dir($jsPath)) {
                    $this->publishes([
                        $jsPath => resource_path('js/Pages/Observability'),
                    ], 'observability-'.$frontend);
                }
            }
        }

        if (config('observability.health.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        }

        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('health-dashboard', \Jeremykenedy\LaravelObservability\Livewire\HealthDashboard::class);
        }

        $detector = $this->app->make(ProviderDetector::class);
        $detector->detect();

        // Blade directive: @observabilityScripts outputs frontend provider JS snippets
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
