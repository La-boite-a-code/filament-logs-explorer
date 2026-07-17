<?php

declare(strict_types=1);

use LaBoiteACode\FilamentLogsExplorer\Support\LogChannelRepository;

it('discovers file based channels and skips the others', function () {
    $this->writeLog('single.log', "a\n");
    $this->writeLog('daily-2026-07-17.log', "b\n");

    $names = (new LogChannelRepository)->channels()->pluck('name');

    expect($names)->toContain('single')
        ->and($names)->toContain('daily')
        ->and($names)->not->toContain('slack')   // no file driver
        ->and($names)->not->toContain('stderr')  // php://stderr, not a file
        ->and($names)->not->toContain('stack');  // expanded into its members
});

it('does not list channels without any file', function () {
    // Only a daily file exists; "single" resolves to a missing file.
    $this->writeLog('daily-2026-07-17.log', "b\n");

    $names = (new LogChannelRepository)->channels()->pluck('name');

    expect($names)->toContain('daily')
        ->and($names)->not->toContain('single');
});

it('limits the number of files per channel, newest first', function () {
    foreach (range(10, 20) as $day) {
        $path = $this->writeLog("daily-2026-07-{$day}.log", "x\n");
        touch($path, mktime(0, 0, 0, 7, $day, 2026));
    }

    $daily = (new LogChannelRepository(filesPerChannel: 3))
        ->channels()
        ->firstWhere('name', 'daily');

    expect($daily->files)->toHaveCount(3)
        ->and($daily->files->first()->name)->toBe('daily-2026-07-20.log');
});

it('respects an explicit, ordered channel list', function () {
    $this->writeLog('single.log', "a\n");
    $this->writeLog('daily-2026-07-17.log', "b\n");

    $names = (new LogChannelRepository(channels: ['daily', 'single']))
        ->channels()
        ->pluck('name')
        ->all();

    expect($names)->toBe(['daily', 'single']);
});

it('excludes configured channels', function () {
    $this->writeLog('single.log', "a\n");
    $this->writeLog('daily-2026-07-17.log', "b\n");

    $names = (new LogChannelRepository(excludeChannels: ['daily']))
        ->channels()
        ->pluck('name');

    expect($names)->toContain('single')
        ->and($names)->not->toContain('daily');
});

it('finds a file by its opaque id and rejects unknown ids', function () {
    $this->writeLog('single.log', "a\n");

    $repository = new LogChannelRepository;
    $file = $repository->files()->first();

    expect($repository->find($file->id())?->path)->toBe($file->path)
        ->and($repository->find('unknown-id'))->toBeNull();
});

it('optionally discovers untracked files', function () {
    $this->writeLog('single.log', "a\n");
    $this->writeLog('orphan.log', "o\n");

    $untracked = (new LogChannelRepository(
        discoverUntracked: true,
        logDirectory: $this->logsPath(),
    ))
        ->channels()
        ->firstWhere('name', '__untracked');

    expect($untracked)->not->toBeNull()
        ->and($untracked->files->pluck('name'))->toContain('orphan.log')
        ->and($untracked->files->pluck('name'))->not->toContain('single.log');
});

it('deduplicates files shared between channels via a stack', function () {
    $this->writeLog('single.log', "a\n");

    // "single" is also a member of "stack"; the file must appear only once.
    $files = (new LogChannelRepository)->files();

    expect($files->where('name', 'single.log'))->toHaveCount(1);
});
