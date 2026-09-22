<?php

declare(strict_types=1);

use Filament\Panel;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;
use LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer;
use LaBoiteACode\FilamentLogsExplorer\Tests\Fixtures\SettingsCluster;

it('files the page under the cluster passed to the plugin', function () {
    $panel = Panel::make()
        ->id('clustered')
        ->path('clustered')
        ->plugin(FilamentLogsExplorerPlugin::make()->cluster(SettingsCluster::class));

    expect($panel->getClusteredComponents(SettingsCluster::class))
        ->toContain(LogsExplorer::class);
});

it('files the page under the configured cluster', function () {
    config()->set('filament-logs-explorer.cluster', SettingsCluster::class);

    $panel = Panel::make()
        ->id('clustered')
        ->path('clustered')
        ->plugin(FilamentLogsExplorerPlugin::make());

    expect($panel->getClusteredComponents(SettingsCluster::class))
        ->toContain(LogsExplorer::class);
});

it('keeps the cluster of one panel out of the others', function () {
    Panel::make()
        ->id('clustered')
        ->path('clustered')
        ->plugin(FilamentLogsExplorerPlugin::make()->cluster(SettingsCluster::class));

    $plain = Panel::make()
        ->id('plain')
        ->path('plain')
        ->plugin(FilamentLogsExplorerPlugin::make());

    expect($plain->getClusteredComponents())->toBe([])
        ->and(LogsExplorer::getCluster())->toBeNull();
});
