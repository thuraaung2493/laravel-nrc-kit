<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Thuraaung\NrcKit\View\Components\NrcSelector;

final class NrcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nrc-kit.php', 'nrc-kit');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'nrc-kit');
        Blade::component(NrcSelector::class, 'nrc-selector');

        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('vendor/nrc-kit'),
        ], 'nrc-kit-assets');

        $this->publishes([
            __DIR__ . '/../config/nrc-kit.php' => config_path('nrc-kit.php'),
        ], 'nrc-kit-config');
    }
}
