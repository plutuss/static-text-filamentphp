<?php

namespace Plutuss\StaticTextFilament;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

final class StaticTextFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/static-text-filament.php', 'static-text-filament');

        $this->app->booting(function (): void {
            $panelId = config('static-text-filament.panel');

            if (!is_string($panelId) || $panelId === '' || !$this->app->bound('filament')) {
                return;
            }

            $panel = Filament::getPanels()[$panelId] ?? null;
            $plugin = StaticTextPlugin::make();

            if ($panel && !$panel->hasTenancy() && !$panel->hasPlugin($plugin->getId())) {
                $panel->plugin($plugin);
            }
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/static-text-filament.php' => config_path('static-text-filament.php'),
        ], 'static-text-filament-config');
    }
}
