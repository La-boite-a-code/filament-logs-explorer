<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentLogsExplorerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-logs-explorer';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            assets: [
                Js::make('filament-logs-explorer', __DIR__.'/../resources/dist/filament-logs-explorer.js'),
                Css::make('filament-logs-explorer', __DIR__.'/../resources/dist/filament-logs-explorer.css'),
            ],
            package: 'laboiteacode/filament-logs-explorer',
        );
    }
}
