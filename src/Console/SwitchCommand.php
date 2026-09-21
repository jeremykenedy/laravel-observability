<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelObservability\Console\Concerns\HandlesFrameworkSetup;

use function Laravel\Prompts\info;

class SwitchCommand extends Command
{
    use HandlesFrameworkSetup;

    protected $signature = 'observability:switch
        {--css= : CSS framework (tailwind, bootstrap5, bootstrap4)}
        {--frontend= : Frontend framework (blade, livewire, vue, react, svelte)}';

    protected $description = 'Switch the CSS and/or frontend framework for Laravel Observability';

    public function handle(): int
    {
        $css = $this->option('css');
        $frontend = $this->option('frontend');

        if (!$css && !$frontend) {
            $this->error('Provide at least one of --css or --frontend.');
            $this->line('');
            $this->line('  Examples:');
            $this->line('    php artisan observability:switch --css=bootstrap5');
            $this->line('    php artisan observability:switch --frontend=livewire');
            $this->line('    php artisan observability:switch --css=tailwind --frontend=vue');

            return self::FAILURE;
        }

        if (!$this->validateFrameworks() || !$this->canSaveFrameworks()) {
            return self::FAILURE;
        }

        $this->saveFrameworks($this->getCssOption(), $this->getFrontendOption());
        info('Observability framework settings saved. Existing configuration and views were preserved.');

        info('Run: php artisan view:clear && npm run build');

        return self::SUCCESS;
    }
}
