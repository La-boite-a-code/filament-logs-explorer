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

it('deletes a file through the confirmed action', function () {
    $path = $this->writeLog('single.log', "delete me\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    expect(is_file($path))->toBeTrue();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->call('callMountedAction')
        ->assertOk();

    expect(is_file($path))->toBeFalse();
});

it('does not delete until the confirmation is submitted', function () {
    $path = $this->writeLog('single.log', "keep until confirmed\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->assertActionMounted('deleteLog');

    expect(is_file($path))->toBeTrue();
});

it('deletes the open file and closes the viewer slide-over', function () {
    $path = $this->writeLog('single.log', "delete me\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'viewLog', ['file' => $id])
        ->assertActionMounted('viewLog')
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->call('callMountedAction')
        ->assertActionNotMounted('viewLog')
        ->assertActionNotMounted('deleteLog');

    expect(is_file($path))->toBeFalse();
});

it('does not delete when the feature is disabled', function () {
    $path = $this->writeLog('single.log', "keep me\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    FilamentLogsExplorerPlugin::get()->deletable(false);

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->call('callMountedAction');

    expect(is_file($path))->toBeTrue();
});

it('respects a configured deletion gate', function () {
    Gate::define('delete-logs', fn () => false);
    config()->set('filament-logs-explorer.deletion.gate', 'delete-logs');

    $path = $this->writeLog('single.log', "keep me\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->call('callMountedAction');

    expect(is_file($path))->toBeTrue();
});

it('is deletable by default', function () {
    expect(FilamentLogsExplorerPlugin::get()->canDelete())->toBeTrue();
});

it('does not even mount the delete action without permission', function () {
    $path = $this->writeLog('single.log', "keep me\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    FilamentLogsExplorerPlugin::get()->deletable(false);

    // The no-argument form asserts that *nothing* is mounted. Passing a name
    // instead returns early without asserting when the action is absent, so it
    // would pass vacuously here.
    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->assertActionNotMounted();

    expect(is_file($path))->toBeTrue();
});

it('does not mount the delete action when the gate denies it', function () {
    Gate::define('delete-logs', fn () => false);
    config()->set('filament-logs-explorer.deletion.gate', 'delete-logs');

    $path = $this->writeLog('single.log', "keep me\n");
    $id = (new LogChannelRepository)->files()->first()->id();

    Livewire::test(LogsExplorer::class)
        ->call('mountAction', 'deleteLog', ['file' => $id])
        ->assertActionNotMounted();

    expect(is_file($path))->toBeTrue();
});

it('shows a delete control in the file list', function () {
    $this->writeLog('single.log', "hello\n");

    Livewire::test(LogsExplorer::class)
        ->assertOk()
        ->assertSee(__('filament-logs-explorer::filament-logs-explorer.viewer.delete'));
});

it('hides the delete control when the feature is disabled', function () {
    $this->writeLog('single.log', "hello\n");

    FilamentLogsExplorerPlugin::get()->deletable(false);

    Livewire::test(LogsExplorer::class)
        ->assertOk()
        ->assertDontSee(__('filament-logs-explorer::filament-logs-explorer.viewer.delete'));
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
