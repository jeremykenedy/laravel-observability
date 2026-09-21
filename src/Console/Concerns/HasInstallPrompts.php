<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console\Concerns;

use function Laravel\Prompts\select;

trait HasInstallPrompts
{
    protected function renderBanner(string $name): void
    {
        $this->info('Laravel Observability');
        $this->newLine();
    }

    /**
     * @return array{css: string, frontend: string}|false
     */
    protected function promptFrameworks(): array|false
    {
        if (!$this->validateFrameworks()) {
            return false;
        }

        $css = $this->option('css');
        $frontend = $this->option('frontend');

        if ($css && $frontend) {
            $validCss = ['tailwind', 'bootstrap5', 'bootstrap4'];
            $validFrontend = ['blade', 'livewire', 'vue', 'react', 'svelte'];

            if (!in_array($css, $validCss)) {
                $this->error("Invalid CSS framework: {$css}. Use: ".implode(', ', $validCss));

                return false;
            }

            if (!in_array($frontend, $validFrontend)) {
                $this->error("Invalid frontend: {$frontend}. Use: ".implode(', ', $validFrontend));

                return false;
            }

            return ['css' => $css, 'frontend' => $frontend];
        }

        if ($this->option('no-interaction')) {
            return [
                'css'      => $css ?: $this->frameworks->css(),
                'frontend' => $frontend ?: $this->frameworks->frontend(),
            ];
        }

        while (true) {
            $cssResult = $this->promptCssFramework();
            if ($cssResult === false) {
                return false;
            }

            $frontendResult = $this->promptFrontendFramework();
            if ($frontendResult === false) {
                return false;
            }
            if ($frontendResult === '__back__') {
                continue;
            }

            $confirmation = $this->promptConfirmation($cssResult, $frontendResult);

            if ($confirmation === 'confirm') {
                return ['css' => $cssResult, 'frontend' => $frontendResult];
            }

            if ($confirmation === 'cancel') {
                $this->info('  Cancelled. No changes were made.');

                return false;
            }
        }
    }

    protected function promptCssFramework(): string|false
    {
        $valid = ['tailwind', 'bootstrap5', 'bootstrap4'];
        $css = $this->option('css');

        if ($css) {
            if (!in_array($css, $valid)) {
                $this->error("Invalid CSS framework: {$css}. Use: ".implode(', ', $valid));

                return false;
            }

            return $css;
        }

        if ($this->option('no-interaction')) {
            return $this->frameworks->css();
        }

        return select(
            label: 'Which CSS framework would you like to use?',
            options: [
                'tailwind'   => 'Tailwind CSS',
                'bootstrap5' => 'Bootstrap 5',
                'bootstrap4' => 'Bootstrap 4',
            ],
            default: $this->frameworks->css(),
        );
    }

    protected function promptFrontendFramework(): string|false
    {
        $valid = ['blade', 'livewire', 'vue', 'react', 'svelte'];
        $frontend = $this->option('frontend');

        if ($frontend) {
            if (!in_array($frontend, $valid)) {
                $this->error("Invalid frontend: {$frontend}. Use: ".implode(', ', $valid));

                return false;
            }

            return $frontend;
        }

        if ($this->option('no-interaction')) {
            return $this->frameworks->frontend();
        }

        return select(
            label: 'Which frontend framework would you like to use?',
            options: [
                '__back__'   => "\033[90m< Back to CSS selection\033[0m",
                'blade'      => 'Blade',
                'livewire'   => 'Livewire',
                'vue'        => 'Vue 3',
                'react'      => 'React',
                'svelte'     => 'Svelte',
            ],
            default: $this->frameworks->frontend(),
        );
    }

    protected function promptConfirmation(string $css, string $frontend): string
    {
        $cssLabels = [
            'tailwind'   => 'Tailwind CSS',
            'bootstrap5' => 'Bootstrap 5',
            'bootstrap4' => 'Bootstrap 4',
        ];

        $frontendLabels = [
            'blade'    => 'Blade',
            'livewire' => 'Livewire',
            'vue'      => 'Vue 3',
            'react'    => 'React',
            'svelte'   => 'Svelte',
        ];

        $this->newLine();
        $this->line("  \033[1mYour selections:\033[0m");
        $this->line("  \033[90mCSS:\033[0m       ".($cssLabels[$css] ?? $css));
        $this->line("  \033[90mFrontend:\033[0m  ".($frontendLabels[$frontend] ?? $frontend));
        $this->newLine();

        return select(
            label: 'Continue with these settings?',
            options: [
                'confirm' => 'Confirm and continue',
                'restart' => 'Start over',
                'cancel'  => 'Cancel and exit',
            ],
            default: 'confirm',
        );
    }

    protected function showSummary(string $packageName, string $css, string $frontend): void
    {
        $cssLabels = [
            'tailwind'   => 'Tailwind CSS',
            'bootstrap5' => 'Bootstrap 5',
            'bootstrap4' => 'Bootstrap 4',
        ];

        $frontendLabels = [
            'blade'    => 'Blade',
            'livewire' => 'Livewire',
            'vue'      => 'Vue 3',
            'react'    => 'React',
            'svelte'   => 'Svelte',
        ];

        $cssLabel = $cssLabels[$css] ?? $css;
        $feLabel = $frontendLabels[$frontend] ?? $frontend;

        $this->newLine();
        $this->line("  \033[32m{$packageName} configured successfully.\033[0m");
        $this->newLine();
        $this->line("  \033[90mCSS:\033[0m       {$cssLabel}");
        $this->line("  \033[90mFrontend:\033[0m  {$feLabel}");
        $this->newLine();
        $this->line("  Run: \033[33mnpm run build\033[0m");
        $this->newLine();
    }
}
