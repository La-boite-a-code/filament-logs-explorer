<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->plugin(FilamentLogsExplorerPlugin::make());
    }
}
