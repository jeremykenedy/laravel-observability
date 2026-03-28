<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'observability:install
        {--css=tailwind : CSS framework (tailwind, bootstrap5, bootstrap4)}
        {--frontend=blade : Frontend framework (blade, livewire, vue, react, svelte)}';

    protected $description = 'Interactively install and configure observability providers for your Laravel app';

    protected array $providers = [
        'backend' => [
            'sentry' => ['label' => 'Sentry (Error Tracking)', 'package' => 'sentry/sentry-laravel', 'keys' => ['SENTRY_LARAVEL_DSN'], 'signup' => 'https://sentry.io/signup/'],
            'bugsnag' => ['label' => 'Bugsnag (Error Monitoring)', 'package' => 'bugsnag/bugsnag-laravel', 'keys' => ['BUGSNAG_API_KEY'], 'signup' => 'https://app.bugsnag.com/user/new'],
            'flare' => ['label' => 'Flare/Ignition (Laravel Error Tracker)', 'package' => 'spatie/laravel-ignition', 'keys' => ['FLARE_KEY'], 'signup' => 'https://flareapp.io/'],
            'rollbar' => ['label' => 'Rollbar (Error + Deploy Tracking)', 'package' => 'rollbar/rollbar-laravel', 'keys' => ['ROLLBAR_TOKEN'], 'signup' => 'https://rollbar.com/signup/'],
            'honeybadger' => ['label' => 'Honeybadger (Errors + Uptime + Cron)', 'package' => 'honeybadger-io/honeybadger-laravel', 'keys' => ['HONEYBADGER_API_KEY'], 'signup' => 'https://www.honeybadger.io/'],
            'airbrake' => ['label' => 'Airbrake (Error + Performance)', 'package' => 'airbrake/phpbrake', 'keys' => ['AIRBRAKE_PROJECT_ID', 'AIRBRAKE_PROJECT_KEY'], 'signup' => 'https://airbrake.io/'],
            'raygun' => ['label' => 'Raygun (Crash Reporting + RUM)', 'package' => 'mindscape/raygun4php', 'keys' => ['RAYGUN_API_KEY'], 'signup' => 'https://raygun.com/'],
            'exception_notifier' => ['label' => 'Laravel Exception Notifier (Email Alerts)', 'package' => 'jeremykenedy/laravel-exception-notifier', 'keys' => [], 'signup' => 'https://github.com/jeremykenedy/laravel-exception-notifier'],
        ],
        'apm' => [
            'new_relic' => ['label' => 'New Relic (Full APM)', 'package' => null, 'keys' => ['NEW_RELIC_LICENSE_KEY', 'NEW_RELIC_APP_NAME'], 'signup' => 'https://newrelic.com/signup', 'note' => 'Requires the New Relic PHP agent installed on your server.'],
            'datadog' => ['label' => 'Datadog (Infrastructure + APM)', 'package' => null, 'keys' => ['DATADOG_API_KEY'], 'signup' => 'https://www.datadoghq.com/', 'note' => 'Requires the Datadog dd-trace PHP extension.'],
            'appsignal' => ['label' => 'AppSignal (Performance Monitoring)', 'package' => 'appsignal/appsignal-laravel', 'keys' => ['APPSIGNAL_PUSH_API_KEY'], 'signup' => 'https://www.appsignal.com/'],
            'loggly' => ['label' => 'Loggly (Cloud Log Management)', 'package' => null, 'keys' => ['LOGGLY_TOKEN'], 'signup' => 'https://www.loggly.com/', 'note' => 'Add a custom Monolog handler in config/logging.php.'],
        ],
        'frontend' => [
            'logrocket' => ['label' => 'LogRocket (Session Replay)', 'npm' => 'logrocket', 'keys' => ['LOGROCKET_APP_ID'], 'signup' => 'https://logrocket.com/', 'blade' => "Add @observabilityScripts to your layout <head> or manually:\nimport LogRocket from 'logrocket';\nLogRocket.init('YOUR_APP_ID');"],
            'instabug' => ['label' => 'Instabug (Bug Reporting)', 'npm' => null, 'keys' => ['INSTABUG_TOKEN'], 'signup' => 'https://www.instabug.com/', 'blade' => 'Add @observabilityScripts to your layout <head>. The Instabug SDK loads via CDN.'],
            'gleap' => ['label' => 'Gleap (Visual Bug Reports + Feedback)', 'npm' => 'gleap', 'keys' => ['GLEAP_API_KEY'], 'signup' => 'https://gleap.io/', 'blade' => "Add @observabilityScripts to your layout <head> or manually:\nimport Gleap from 'gleap';\nGleap.initialize('YOUR_API_KEY');"],
        ],
        'testing' => [
            'ghost_inspector' => ['label' => 'Ghost Inspector (Browser Testing)', 'package' => null, 'keys' => ['GHOST_INSPECTOR_API_KEY'], 'signup' => 'https://ghostinspector.com/'],
            'lighthouse' => ['label' => 'Google Lighthouse (Performance Auditing)', 'package' => null, 'keys' => [], 'signup' => 'https://developer.chrome.com/docs/lighthouse/overview/', 'note' => "Run: npx lighthouse https://your-app.com --output json --chrome-flags=\"--headless\""],
            'link_checker' => ['label' => 'Spatie Link Checker (Broken Links)', 'package' => 'spatie/laravel-link-checker', 'keys' => [], 'signup' => 'https://github.com/spatie/laravel-link-checker'],
        ],
        'uptime' => [
            'uptimerobot' => ['label' => 'UptimeRobot (Uptime Monitoring)', 'package' => null, 'keys' => ['UPTIMEROBOT_API_KEY'], 'signup' => 'https://uptimerobot.com/'],
            'statuscake' => ['label' => 'StatusCake (Website Monitoring)', 'package' => null, 'keys' => ['STATUSCAKE_API_KEY'], 'signup' => 'https://www.statuscake.com/'],
        ],
    ];

    public function handle(): int
    {
        $this->newLine();
        info('  ╔══════════════════════════════════════════════╗');
        info('  ║       Laravel Observability Installer        ║');
        info('  ║   Add monitoring to your app in minutes.     ║');
        info('  ╚══════════════════════════════════════════════╝');
        $this->newLine();

        // Check .env
        $envPath = base_path('.env');
        $envExists = file_exists($envPath);
        if (! $envExists) {
            warning('No .env file found. Credentials will be displayed but not saved automatically.');
        }

        // Step 1: Select backend providers
        info('Step 1/5: Backend Error Tracking & Monitoring');
        $backendChoices = multiselect(
            label: 'Select backend providers to install:',
            options: collect($this->providers['backend'])->mapWithKeys(fn ($p, $k) => [$k => $p['label']])->all(),
            hint: 'Space to select, Enter to confirm. These auto-integrate with your Laravel app.',
        );

        // Step 2: APM
        info('Step 2/5: APM & Performance Monitoring');
        $apmChoices = multiselect(
            label: 'Select APM providers:',
            options: collect($this->providers['apm'])->mapWithKeys(fn ($p, $k) => [$k => $p['label']])->all(),
            hint: 'These monitor your app performance and log management.',
        );

        // Step 3: Frontend
        info('Step 3/5: Frontend Monitoring');
        $frontendChoices = multiselect(
            label: 'Select frontend monitoring providers:',
            options: collect($this->providers['frontend'])->mapWithKeys(fn ($p, $k) => [$k => $p['label']])->all(),
            hint: 'These monitor your browser/client-side experience.',
        );

        // Step 4: Testing & Uptime
        info('Step 4/5: Testing, Quality & Uptime');
        $testingOptions = collect($this->providers['testing'])->merge($this->providers['uptime'])
            ->mapWithKeys(fn ($p, $k) => [$k => $p['label']])->all();
        $testingChoices = multiselect(
            label: 'Select testing & uptime providers:',
            options: $testingOptions,
            hint: 'Quality assurance and availability monitoring.',
        );

        $allSelected = array_merge($backendChoices, $apmChoices, $frontendChoices, $testingChoices);

        if (empty($allSelected)) {
            warning('No providers selected. Publishing config only.');
            $this->call('vendor:publish', ['--tag' => 'observability-config', '--force' => true]);
            info('Config published. Run observability:install again to add providers.');

            return self::SUCCESS;
        }

        // Step 5: Confirmation
        $this->newLine();
        info('Step 5/5: Confirm Selection');
        $this->newLine();

        $tableRows = [];
        foreach ($allSelected as $name) {
            $provider = $this->findProvider($name);
            $type = $this->findProviderType($name);
            $install = $provider['package'] ?? ($provider['npm'] ?? 'Config only');
            $tableRows[] = [$name, $type, $install];
        }

        table(['Provider', 'Type', 'Package'], $tableRows);

        if (! confirm('Install these providers?', true)) {
            warning('Installation cancelled.');

            return self::FAILURE;
        }

        // Publish config
        spin(fn () => $this->callSilent('vendor:publish', ['--tag' => 'observability-config', '--force' => true]), 'Publishing configuration...');

        // Install composer packages
        $composerPackages = [];
        foreach ($allSelected as $name) {
            $provider = $this->findProvider($name);
            if (! empty($provider['package'])) {
                $composerPackages[] = $provider['package'];
            }
        }

        if (! empty($composerPackages)) {
            $pkgList = implode(' ', $composerPackages);
            info("Installing composer packages: {$pkgList}");
            spin(
                fn () => exec("cd ".base_path()." && composer require {$pkgList} 2>&1", $output, $code),
                'Installing composer packages...',
            );
        }

        // Install npm packages
        $npmPackages = [];
        foreach ($allSelected as $name) {
            $provider = $this->findProvider($name);
            if (! empty($provider['npm'])) {
                $npmPackages[] = $provider['npm'];
            }
        }

        if (! empty($npmPackages)) {
            $npmList = implode(' ', $npmPackages);
            info("Installing npm packages: {$npmList}");
            spin(
                fn () => exec("cd ".base_path()." && npm install {$npmList} 2>&1", $output, $code),
                'Installing npm packages...',
            );
        }

        // Collect credentials
        $this->newLine();
        info('Enter your credentials (leave blank to skip, you can add later in .env):');
        $this->newLine();

        $envValues = [];
        foreach ($allSelected as $name) {
            $provider = $this->findProvider($name);
            if (empty($provider['keys'])) {
                continue;
            }

            info("  {$provider['label']}");

            $envValues[strtoupper($name).'_ENABLED'] = 'true';

            foreach ($provider['keys'] as $key) {
                $value = text(
                    label: "  {$key}",
                    placeholder: 'Paste your key here or leave blank',
                    hint: "Get this from: {$provider['signup']}",
                );
                if ($value) {
                    $envValues[$key] = $value;
                }
            }
            $this->newLine();
        }

        // Write to .env
        if (! empty($envValues) && $envExists) {
            spin(function () use ($envPath, $envValues) {
                $content = file_get_contents($envPath);
                foreach ($envValues as $key => $value) {
                    if (str_contains($content, "{$key}=")) {
                        $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
                    } else {
                        $content .= "\n{$key}={$value}";
                    }
                }
                file_put_contents($envPath, $content);
            }, 'Saving credentials to .env...');
        } elseif (! $envExists && ! empty($envValues)) {
            warning('Could not write to .env (file does not exist). Add these manually:');
            foreach ($envValues as $key => $value) {
                $this->line("  {$key}={$value}");
            }
        }

        // Clear config cache
        $this->callSilent('config:clear');

        // Results
        $this->newLine();
        info('  ╔══════════════════════════════════════════════╗');
        info('  ║         Installation Complete!               ║');
        info('  ╚══════════════════════════════════════════════╝');
        $this->newLine();

        // Frontend instructions
        $hasFrontend = ! empty(array_intersect($allSelected, array_keys($this->providers['frontend'])));
        if ($hasFrontend) {
            note(<<<'NOTE'
            FRONTEND SETUP REQUIRED

            Add this to your Blade layout <head> tag to auto-inject
            monitoring scripts for all enabled frontend providers:

                @observabilityScripts

            This outputs <script> tags with your credentials for
            LogRocket, Instabug, Gleap, etc.
            NOTE);
            $this->newLine();
        }

        // Per-provider instructions
        foreach ($allSelected as $name) {
            $provider = $this->findProvider($name);
            $this->line("  <fg=cyan;options=bold>{$provider['label']}</>");

            if (! empty($provider['signup'])) {
                $this->line("    Get credentials: <href={$provider['signup']}>{$provider['signup']}</>");
            }

            if (! empty($provider['note'])) {
                $this->line("    Note: {$provider['note']}");
            }

            if (! empty($provider['blade'])) {
                $this->line("    Frontend: {$provider['blade']}");
            }

            if (! empty($provider['package'])) {
                $this->line("    <fg=green>Composer package installed automatically.</>");
            }

            $this->newLine();
        }

        info('Run `php artisan config:clear` if you update .env values.');
        info('Health check: GET /health');
        info('Active providers: GET /health/providers');

        return self::SUCCESS;
    }

    protected function findProvider(string $name): array
    {
        foreach ($this->providers as $providers) {
            if (isset($providers[$name])) {
                return $providers[$name];
            }
        }

        return ['label' => $name, 'keys' => [], 'signup' => ''];
    }

    protected function findProviderType(string $name): string
    {
        foreach ($this->providers as $type => $providers) {
            if (isset($providers[$name])) {
                return $type;
            }
        }

        return 'unknown';
    }
}
