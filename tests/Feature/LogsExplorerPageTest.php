<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;
use LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer;
use LaBoiteACode\FilamentLogsExplorer\Support\LogChannelRepository;
use Livewire\Livewire;

it('renders successfully and lists the discovered files', function () {
    $this->writeLog('single.log', "hello world\n");

    Livewire::test(LogsExplorer::class)
        ->assertOk()
        ->assertSee('single.log');
});

it('renders an empty state when there is no log file', function () {
    Livewire::test(LogsExplorer::class)
        ->assertOk()
        ->assertSee(__('filament-logs-explorer::filament-logs-explorer.list.empty.heading'));
});

it('opens a file in the slide-over action', function () {
    $this->writeLog('single.log', "hello world\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'viewLog', ['file' => $id])
        ->assertActionMounted('viewLog')
        ->assertSee('hello world')
        ->assertSee(__('filament-logs-explorer::filament-logs-explorer.viewer.search_placeholder'));
});

it('moves between files while the slide-over stays open', function () {
    $this->writeLog('single.log', "first file content\n");
    $this->writeLog('daily-2026-07-17.log', "second file content\n");

    $files = (new LogChannelRepository)->files();
    $first = $files->get(0)->id();
    $second = $files->get(1)->id();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'viewLog', ['file' => $first])
        ->assertSee('first file content')
        ->call('replaceMountedAction', 'viewLog', ['file' => $second])
        ->assertSee('second file content');
});

it('computes the previous and next file within the flat list', function () {
    $this->writeLog('single.log', "a\n");
    $this->writeLog('daily-2026-07-17.log', "b\n");

    $files = (new LogChannelRepository)->files();
    $first = $files->get(0)->id();
    $second = $files->get(1)->id();

    $page = Livewire::test(LogsExplorer::class)->instance();

    expect($page->previousFileId($first))->toBeNull()
        ->and($page->nextFileId($first))->toBe($second)
        ->and($page->previousFileId($second))->toBe($first)
        ->and($page->nextFileId($second))->toBeNull();
});

it('downloads a file', function () {
    $this->writeLog('single.log', "downloadable\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    Livewire::test(LogsExplorer::class)
        ->call('downloadFile', $id)
        ->assertFileDownloaded('single.log');
});

it('refreshes without error', function () {
    $this->writeLog('single.log', "a\n");

    Livewire::test(LogsExplorer::class)
        ->call('refreshLogs')
        ->assertOk();
});

it('is accessible by default', function () {
    expect(LogsExplorer::canAccess())->toBeTrue();
});

it('respects a configured authorization gate', function () {
    Gate::define('view-logs', fn () => false);
    config()->set('filament-logs-explorer.authorization.gate', 'view-logs');

    expect(LogsExplorer::canAccess())->toBeFalse();
});

it('lets a fluent navigation icon drive both the normal and active states', function () {
    FilamentLogsExplorerPlugin::get()->navigationIcon('heroicon-o-cog');

    expect(LogsExplorer::getNavigationIcon())->toBe('heroicon-o-cog')
        ->and(LogsExplorer::getActiveNavigationIcon())->toBe('heroicon-o-cog');
});

it('still falls back to the configured active_icon default when nothing is customised', function () {
    expect(LogsExplorer::getActiveNavigationIcon())->toBe('heroicon-s-document-magnifying-glass');
});

it('uses the translated navigation label for the current locale', function () {
    app()->setLocale('fr');
    expect(LogsExplorer::getNavigationLabel())->toBe('Journaux');

    app()->setLocale('es');
    expect(LogsExplorer::getNavigationLabel())->toBe('Registros');

    app()->setLocale('en');
    expect(LogsExplorer::getNavigationLabel())->toBe('Logs');
});
