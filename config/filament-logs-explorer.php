<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Customise how the plugin registers itself in the Filament panel
    | navigation. Every value here can also be overridden at runtime through
    | the plugin's fluent API, e.g.:
    |
    |     FilamentLogsExplorerPlugin::make()
    |         ->navigationLabel('Journaux')
    |         ->navigationIcon('heroicon-o-bug-ant')
    |         ->navigationGroup('System')
    |         ->navigationSort(99);
    |
    */

    'navigation' => [
        'register' => true,
        'sort' => null,
        'group' => null,
        'label' => null,
        'icon' => 'heroicon-o-document-magnifying-glass',
        'active_icon' => 'heroicon-s-document-magnifying-glass',
        // Show the number of discovered channels as a navigation badge.
        'badge' => false,
    ],

    // The URL slug used for the page inside the panel.
    'slug' => 'logs',

    // Optionally attach the page to a Filament cluster (FQCN) or leave null.
    'cluster' => null,

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    |
    | The plugin resolves log files from the channels declared in your
    | config/logging.php file.
    |
    | - Leave "channels" empty to auto-discover every file based channel
    |   (drivers: single, daily, monolog with a stream handler, and the
    |   file based members of any "stack" channel).
    | - Or provide an explicit, ordered list of channel names to restrict and
    |   sort what is displayed, e.g. ['daily', 'single'].
    |
    */

    'channels' => [
        // 'daily',
        // 'single',
    ],

    // Channel names to always hide, even when auto-discovering.
    'exclude_channels' => [],

    // Expand "stack" channels into their file based members.
    'expand_stacks' => true,

    /*
    |--------------------------------------------------------------------------
    | Untracked files (directory scan)
    |--------------------------------------------------------------------------
    |
    | When enabled, the plugin also scans the log directory for *.log files
    | that are not attached to any resolved channel and groups them under a
    | dedicated "untracked" channel. Disabled by default so that only files
    | you actually log to through channels are shown.
    |
    */

    'discover_untracked_files' => false,

    // Directory scanned when "discover_untracked_files" is true.
    // Null falls back to storage_path('logs').
    'log_directory' => null,

    // Null falls back to the translated label.
    'untracked_channel_label' => null,

    /*
    |--------------------------------------------------------------------------
    | Files per channel
    |--------------------------------------------------------------------------
    |
    | How many of the most recent files to list for each channel (mostly
    | relevant for the "daily" driver which rotates files every day).
    |
    */

    'files_per_channel' => 15,

    /*
    |--------------------------------------------------------------------------
    | Reader
    |--------------------------------------------------------------------------
    |
    | How log file contents are loaded into the slide-over viewer. Files larger
    | than "max_bytes" are truncated to keep the UI responsive. When
    | "tail_when_exceeded" is true the END of the file (most recent entries) is
    | loaded, otherwise the beginning is loaded.
    |
    */

    'reader' => [
        'max_bytes' => 5 * 1024 * 1024, // 5 MB
        'tail_when_exceeded' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Controls the page's canAccess() method. By default the page is available
    | to anyone who can access the panel. Set a Gate ability name to restrict
    | it, or override the whole check through the plugin's fluent API:
    |
    |     FilamentLogsExplorerPlugin::make()
    |         ->canAccessUsing(fn (): bool => auth()->user()?->isAdmin() ?? false);
    |
    */

    'authorization' => [
        'gate' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deletion
    |--------------------------------------------------------------------------
    |
    | Controls the "delete file" feature: a trash button on each file in the
    | list and in the viewer that permanently removes the file from disk. It is
    | enabled by default; disable it entirely, restrict it to a Gate ability, or
    | override the whole check through the plugin's fluent API:
    |
    |     FilamentLogsExplorerPlugin::make()
    |         ->deletable(false);
    |
    |     FilamentLogsExplorerPlugin::make()
    |         ->canDeleteUsing(fn (): bool => auth()->user()?->isAdmin() ?? false);
    |
    */

    'deletion' => [
        'enabled' => true,
        'gate' => null,
    ],

];
