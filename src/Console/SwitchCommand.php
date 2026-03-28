<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Console;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelUiKit\Console\Concerns\HandlesFrameworkSetup;

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

        if (! $css && ! $frontend) {
            $this->error('Provide --css and/or --frontend');

            return self::FAILURE;
        }

        if ($css) {
            $this->setCssFramework($css);
            $this->info("Observability CSS switched to: {$css}");
        }
        if ($frontend) {
            $this->setFrontendFramework($frontend);
            $this->info("Observability frontend switched to: {$frontend}");
        }

        return self::SUCCESS;
    }
}
