<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Support;

use Illuminate\Contracts\Config\Repository;

class FrameworkSettings
{
    public const CSS = ['tailwind', 'bootstrap5', 'bootstrap4'];

    public const FRONTENDS = ['blade', 'livewire', 'vue', 'react', 'svelte'];

    public function __construct(protected Repository $config)
    {
    }

    public function css(): string
    {
        $css = $this->config->get('observability.css_framework') ?? $this->config->get('ui-kit.css_framework', 'tailwind');

        return in_array($css, self::CSS, true) ? $css : 'tailwind';
    }

    public function frontend(): string
    {
        $frontend = $this->config->get('observability.frontend') ?? $this->config->get('ui-kit.frontend', 'blade');

        return in_array($frontend, self::FRONTENDS, true) ? $frontend : 'blade';
    }
}
