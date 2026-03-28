<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class UpdateCommand extends Command
{
    protected $signature = 'observability:update';

    protected $description = 'Update observability configuration and credentials';

    public function handle(): int
    {
        $this->newLine();
        info('  ╔══════════════════════════════════════════════╗');
        info('  ║       Laravel Observability Updater          ║');
        info('  ╚══════════════════════════════════════════════╝');
        $this->newLine();

        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            warning('No .env file found.');

            return self::FAILURE;
        }

        // Show current state
        $detector = app(\Jeremykenedy\LaravelObservability\Services\ProviderDetector::class);
        $detector->detect();

        $active = $detector->getActiveProviders();
        $detected = $detector->getDetected();

        info('Current status:');
        $this->line('  Detected: '.implode(', ', $detected ?: ['none']));
        $this->line('  Active:   '.implode(', ', $active ?: ['none']));
        $this->newLine();

        // Options
        $action = \Laravel\Prompts\select(
            label: 'What would you like to do?',
            options: [
                'config'      => 'Re-publish config (update to latest version)',
                'credentials' => 'Update credentials for active providers',
                'toggle'      => 'Enable/disable a provider',
                'status'      => 'Show detailed provider status',
            ],
        );

        match ($action) {
            'config'      => $this->republishConfig(),
            'credentials' => $this->updateCredentials($envPath),
            'toggle'      => $this->toggleProvider($envPath),
            'status'      => $this->showStatus($detector),
        };

        return self::SUCCESS;
    }

    protected function republishConfig(): void
    {
        if (confirm('This will overwrite your published config. Continue?', false)) {
            spin(
                fn () => $this->callSilent('vendor:publish', ['--tag' => 'observability-config', '--force' => true]),
                'Publishing latest config...',
            );
            $this->callSilent('config:clear');
            info('Config updated to latest version.');
        }
    }

    protected function updateCredentials(string $envPath): void
    {
        $providers = config('observability.providers', []);
        $content = file_get_contents($envPath);

        foreach ($providers as $name => $config) {
            if (!($config['enabled'] ?? false)) {
                continue;
            }

            info("  {$name}:");

            // Find credential keys from config
            $keys = array_filter(array_keys($config), fn ($k) => in_array($k, [
                'dsn', 'api_key', 'key', 'access_token', 'project_id', 'project_key',
                'license_key', 'app_name', 'push_api_key', 'token', 'tag', 'app_id', 'suite_id',
            ]));

            foreach ($keys as $key) {
                $envKey = strtoupper($name).'_'.strtoupper($key);
                // Try common env key patterns
                $envKey = match (true) {
                    str_contains($content, 'SENTRY_LARAVEL_DSN') && $name === 'sentry' => 'SENTRY_LARAVEL_DSN',
                    default                                                            => strtoupper($name).'_'.strtoupper($key),
                };

                $current = env($envKey, '');
                $masked = $current ? substr($current, 0, 4).'...'.substr($current, -4) : '(empty)';

                $value = text(
                    label: "  {$envKey} (current: {$masked})",
                    placeholder: 'Enter new value or leave blank to keep current',
                );

                if ($value) {
                    if (str_contains($content, "{$envKey}=")) {
                        $content = preg_replace("/^{$envKey}=.*/m", "{$envKey}={$value}", $content);
                    } else {
                        $content .= "\n{$envKey}={$value}";
                    }
                }
            }
        }

        file_put_contents($envPath, $content);
        $this->callSilent('config:clear');
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

        $content = file_get_contents($envPath);
        $newValue = $new ? 'true' : 'false';

        if (str_contains($content, "{$envKey}=")) {
            $content = preg_replace("/^{$envKey}=.*/m", "{$envKey}={$newValue}", $content);
        } else {
            $content .= "\n{$envKey}={$newValue}";
        }

        file_put_contents($envPath, $content);
        $this->callSilent('config:clear');
        info("{$selected} is now ".($new ? 'ENABLED' : 'DISABLED'));
    }

    protected function showStatus($detector): void
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
