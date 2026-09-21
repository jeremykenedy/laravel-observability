<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelObservability\Console\Concerns\HandlesFrameworkSetup;
use Jeremykenedy\LaravelObservability\Console\Concerns\HasInstallPrompts;
use Jeremykenedy\LaravelObservability\Services\ProviderDetector;

use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\table;

class UpdateCommand extends Command
{
    use HandlesFrameworkSetup;
    use HasInstallPrompts;

    protected $signature = 'observability:update
        {--css= : CSS framework (tailwind, bootstrap5, bootstrap4)}
        {--frontend= : Frontend framework (blade, livewire, vue, react, svelte)}
        {--ui-kit : Use the installed Laravel UI Kit framework settings}';

    protected $description = 'Update the CSS/frontend framework and manage observability providers';

    public function handle(ProviderDetector $detector): int
    {
        if (!$this->validateFrameworks() || !$this->canSaveFrameworks()) {
            return self::FAILURE;
        }

        $this->renderBanner('OBSERVE');

        if (!$this->isInstalled()) {
            $this->warn('  Laravel Observability is not installed yet.');
            $this->newLine();
            $this->line('  Run the install command first:');
            $this->line('    <comment>php artisan observability:install</comment>');

            return self::FAILURE;
        }

        $css = $this->option('css');
        $frontend = $this->option('frontend');

        if ($css !== null || $frontend !== null || !$this->input->isInteractive()) {
            $this->saveFrameworks($this->getCssOption(), $this->getFrontendOption());
            $this->info('Framework settings saved. Existing configuration and views were preserved.');
            $this->info('Run npm run build if your application bundles frontend assets.');

            return self::SUCCESS;
        }

        $envPath = $this->environment->path();

        $detector->detect();

        $active = $detector->getActiveProviders();
        $detected = $detector->getDetected();

        info('Current status:');
        $this->line('  Detected: '.implode(', ', $detected ?: ['none']));
        $this->line('  Active:   '.implode(', ', $active ?: ['none']));
        $this->newLine();

        $action = \Laravel\Prompts\select(
            label: 'What would you like to do?',
            options: [
                'frameworks'  => 'Change CSS/frontend framework',
                'config'      => 'Publish missing configuration',
                'credentials' => 'Update credentials for active providers',
                'toggle'      => 'Enable/disable a provider',
                'status'      => 'Show detailed provider status',
            ],
        );

        match ($action) {
            'frameworks'  => $this->updateFrameworks(),
            'config'      => $this->republishConfig(),
            'credentials' => $this->updateCredentials($envPath),
            'toggle'      => $this->toggleProvider($envPath),
            'status'      => $this->showStatus($detector),
        };

        return self::SUCCESS;
    }

    protected function isInstalled(): bool
    {
        return file_exists(config_path('observability.php'));
    }

    protected function updateFrameworks(): void
    {
        $result = $this->promptFrameworks();
        if ($result === false) {
            return;
        }

        $this->saveFrameworks($result['css'], $result['frontend']);

        info("CSS framework updated to: {$result['css']}");
        info("Frontend framework updated to: {$result['frontend']}");
        info('Run: php artisan view:clear && npm run build');
    }

    protected function republishConfig(): void
    {
        $this->call('vendor:publish', ['--tag' => 'observability-config']);
        $this->info('Existing configuration was preserved. See the package config for new options.');
    }

    protected function updateCredentials(string $envPath): void
    {
        $values = [];
        $aliases = ['sentry.dsn' => 'SENTRY_LARAVEL_DSN', 'rollbar.access_token' => 'ROLLBAR_TOKEN'];

        foreach (config('observability.providers', []) as $name => $provider) {
            if (!($provider['enabled'] ?? false)) {
                continue;
            }

            foreach (['dsn', 'api_key', 'key', 'access_token', 'project_id', 'project_key', 'license_key', 'app_name', 'push_api_key', 'token', 'tag', 'app_id', 'suite_id'] as $key) {
                if (!array_key_exists($key, $provider)) {
                    continue;
                }

                $envKey = $aliases[$name.'.'.$key] ?? strtoupper($name.'_'.$key);
                $value = password(label: $envKey, hint: 'Leave blank to keep the current value.');

                if ($value !== '') {
                    $values[$envKey] = $value;
                }
            }
        }

        if ($values !== []) {
            $this->environment->update($values);
            $this->callSilent('config:clear');
        }

        info('Credentials updated.');
    }

    protected function toggleProvider(string $envPath): void
    {
        $providers = config('observability.providers', []);
        $options = [];
        foreach ($providers as $name => $config) {
            $status = ($config['enabled'] ?? false) ? 'ON' : 'OFF';
            $options[$name] = "[{$status}] {$name}";
        }

        $selected = \Laravel\Prompts\select(
            label: 'Select provider to toggle:',
            options: $options,
        );

        $current = config("observability.providers.{$selected}.enabled", false);
        $new = !$current;
        $envKey = strtoupper($selected).'_ENABLED';

        $this->environment->update([$envKey => $new ? 'true' : 'false']);
        $this->callSilent('config:clear');
        info("{$selected} is now ".($new ? 'ENABLED' : 'DISABLED'));
    }

    protected function showStatus(ProviderDetector $detector): void
    {
        $providers = config('observability.providers', []);
        $rows = [];

        foreach ($providers as $name => $config) {
            $enabled = ($config['enabled'] ?? false) ? 'Yes' : 'No';
            $detected = in_array($name, $detector->getDetected()) ? 'Yes' : 'No';
            $type = $config['type'] ?? 'unknown';
            $rows[] = [$name, $type, $enabled, $detected];
        }

        table(['Provider', 'Type', 'Enabled', 'Detected'], $rows);

        $uptime = config('observability.uptime', []);
        if (!empty($uptime)) {
            $this->newLine();
            info('Uptime Monitors:');
            foreach ($uptime as $name => $config) {
                $status = ($config['enabled'] ?? false) ? 'Enabled' : 'Disabled';
                $this->line("  {$name}: {$status}");
            }
        }
    }
}
