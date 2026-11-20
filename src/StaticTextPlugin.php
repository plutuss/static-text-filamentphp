<?php

namespace Plutuss\StaticTextFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Plutuss\StaticTextFilament\Resources\BlockResource;
use Plutuss\StaticTextFilament\Resources\PageResource;

final class StaticTextPlugin implements Plugin
{
    public static function make(): self
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'static-text';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PageResource::class, BlockResource::class]);
    }

    public function boot(Panel $panel): void
    {
    }
}
