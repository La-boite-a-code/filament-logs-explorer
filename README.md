# Filament Logs Explorer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laboiteacode/filament-logs-explorer.svg?style=flat-square)](https://packagist.org/packages/laboiteacode/filament-logs-explorer)
[![Tests](https://img.shields.io/github/actions/workflow/status/la-boite-a-code/filament-logs-explorer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/la-boite-a-code/filament-logs-explorer/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laboiteacode/filament-logs-explorer.svg?style=flat-square)](https://packagist.org/packages/laboiteacode/filament-logs-explorer)
[![License](https://img.shields.io/packagist/l/laboiteacode/filament-logs-explorer.svg?style=flat-square)](LICENSE.md)

Browse, read and search your Laravel **log files** straight from your Filament
panel — grouped by logging channel, opened in a slide-over with quick
navigation and in-file search.

- 📂 **Lists the most recent log files per channel**, resolved from your
  `config/logging.php` (`single`, `daily`, `monolog` stream, and the file based
  members of a `stack`).
- 🔎 **Slide-over viewer** with in-file search (highlighting + match
  navigation), jump to start / end, previous / next file, download and keyboard
  shortcuts.
- ⚙️ **Fully configurable** — channels, number of files, menu, reader limits —
  through a config file *and* a fluent plugin API.
- 🔒 **Authorization** via `canAccess()` (Gate ability or a custom closure).
- 🌍 **Translated** into French, English and Spanish.
- 🧩 Works with **Filament v4 and v5**, Laravel 11 / 12 / 13, PHP 8.2 → 8.5.

## Requirements

- PHP 8.2, 8.3, 8.4 or 8.5
- Laravel 11, 12 or 13
- Filament 4 or 5

## Installation

Install the package via Composer:

```bash
composer require laboiteacode/filament-logs-explorer
```

Register the plugin on the Filament panel(s) where you want it to appear — most
commonly in your `app/Providers/Filament/AdminPanelProvider.php`:

```php
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentLogsExplorerPlugin::make());
}
```

That is all you need: a **Logs** entry appears in the navigation and lists every
file based channel it can find.

The viewer's CSS/JS are registered with Filament automatically. In production,
make sure they are published like any other Filament assets:

```bash
php artisan filament:assets
```

Optionally publish the config file and the translations:

```bash
php artisan vendor:publish --tag="filament-logs-explorer-config"
php artisan vendor:publish --tag="filament-logs-explorer-translations"
```

And, if you want to fully customise the markup, the views:

```bash
php artisan vendor:publish --tag="filament-logs-explorer-views"
```

## What it looks like

The **Logs** page lists, for every channel, its most recent files (name, size,
last modified). Clicking a file opens it in a **slide-over** where you can:

| Action | How |
| --- | --- |
| Search in the file | Type in the search box (matches are highlighted) |
| Jump between matches | `↑` / `↓` buttons, or `n` / `N` |
| Go to the start / end of the file | Buttons, or `g` / `G` |
| Open the previous / next file | `‹` / `›` buttons (without leaving the slide-over) |
| Focus the search box | `/` |
| Download the raw file | Download button |

Large files are truncated to keep the UI responsive (see
[Large files](#large-files)).

## Configuration

Every option below can be set **globally** in the published
`config/filament-logs-explorer.php` file, or **per-panel** through the fluent
plugin API (the fluent value always wins).

### Channels

By default the plugin auto-discovers every file based channel declared in
`config/logging.php`. You can restrict and order them:

```php
// config/filament-logs-explorer.php
'channels' => ['daily', 'single'],   // empty => auto-discover
'exclude_channels' => ['emergency'],
'expand_stacks' => true,             // expand "stack" channels into their members
```

```php
FilamentLogsExplorerPlugin::make()
    ->channels(['daily', 'single'])
    ->excludeChannels(['emergency'])
    ->filesPerChannel(20);           // how many recent files to list per channel
```

Want to surface `*.log` files that are not attached to any channel? Enable the
directory scan:

```php
'discover_untracked_files' => true,
'log_directory' => storage_path('logs'), // null => storage_path('logs')
```

```php
FilamentLogsExplorerPlugin::make()
    ->discoverUntrackedFiles(directory: storage_path('logs'));
```

### Customising the navigation entry

```php
FilamentLogsExplorerPlugin::make()
    ->navigationLabel('Application logs')
    ->navigationIcon('heroicon-o-bug-ant')
    ->activeNavigationIcon('heroicon-s-bug-ant')
    ->navigationGroup('System')
    ->navigationSort(99)
    ->navigationBadge()          // show the number of channels as a badge
    ->navigationParentItem('Tools')
    ->slug('application-logs')
    ->registerNavigation(true);  // set to false to hide it from the menu
```

The equivalent config keys live under `navigation` and `slug`.

### Permissions

By default the page is available to anyone who can access the panel. Restrict it
with a Gate ability…

```php
// config/filament-logs-explorer.php
'authorization' => [
    'gate' => 'view-logs',
],
```

…or with a closure, which takes precedence over everything else:

```php
FilamentLogsExplorerPlugin::make()
    ->canAccessUsing(fn (): bool => auth()->user()?->can('viewLogs') ?? false);
```

This drives the page's `canAccess()` method, so it controls both the navigation
visibility and the route authorization.

### Large files

To stay responsive, files larger than `reader.max_bytes` are truncated. By
default the **end** of the file (the most recent entries) is loaded; a banner
tells the user the file was truncated and invites them to download it.

```php
'reader' => [
    'max_bytes' => 5 * 1024 * 1024, // 5 MB
    'tail_when_exceeded' => true,   // false => load the beginning instead
],
```

```php
FilamentLogsExplorerPlugin::make()
    ->maxBytes(10 * 1024 * 1024)
    ->tailWhenExceeded();
```

## Translations

The package ships with **French**, **English** and **Spanish** translations and
follows your application locale. Publish them to customise the wording, or to
add a new language:

```bash
php artisan vendor:publish --tag="filament-logs-explorer-translations"
```

Then edit / create `lang/vendor/filament-logs-explorer/{locale}/filament-logs-explorer.php`.

## Testing

```bash
composer test
```

## Security

The viewer only ever reads files it resolved itself from your logging
configuration: the front-end references files by an opaque, non-reversible id
(never a path), so raw or user-supplied paths are never read from disk.

If you discover a security issue, please email
[alexandre@laboiteacode.fr](mailto:alexandre@laboiteacode.fr) instead of using
the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for what has changed recently.

## Credits

- [Alexandre Ribes](https://github.com/la-boite-a-code)
- [La boîte à code](https://laboiteacode.fr)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
