<?php

declare(strict_types=1);

use LaBoiteACode\FilamentLogsExplorer\Data\LogFile;
use LaBoiteACode\FilamentLogsExplorer\Support\LogFileReader;

it('reads a small file fully', function () {
    $path = $this->writeLog('single.log', "line 1\nline 2\nline 3");
    $file = LogFile::fromPath('single', 'Single', $path);

    $content = (new LogFileReader(maxBytes: 1024))->read($file);

    expect($content->readable)->toBeTrue()
        ->and($content->truncated)->toBeFalse()
        ->and($content->position)->toBe('full')
        ->and($content->content)->toBe("line 1\nline 2\nline 3")
        ->and($content->lineCount)->toBe(3);
});

it('returns an unreadable result for a missing file', function () {
    $file = LogFile::fromPath('single', 'Single', $this->logsPath().'/missing.log');

    $content = (new LogFileReader)->read($file);

    expect($content->readable)->toBeFalse()
        ->and($content->content)->toBe('');
});

it('handles an empty file', function () {
    $path = $this->writeLog('single.log', '');
    $file = LogFile::fromPath('single', 'Single', $path);

    $content = (new LogFileReader)->read($file);

    expect($content->readable)->toBeTrue()
        ->and($content->isEmpty())->toBeTrue()
        ->and($content->lineCount)->toBe(0);
});

it('normalizes windows line endings', function () {
    $path = $this->writeLog('single.log', "a\r\nb\r\nc");
    $file = LogFile::fromPath('single', 'Single', $path);

    $content = (new LogFileReader)->read($file);

    expect($content->content)->toBe("a\nb\nc")
        ->and($content->lineCount)->toBe(3);
});

it('tails large files and drops the partial first line', function () {
    $body = collect(range(1, 200))->map(fn (int $i): string => "line {$i}")->implode("\n");
    $path = $this->writeLog('single.log', $body);
    $file = LogFile::fromPath('single', 'Single', $path);

    $content = (new LogFileReader(maxBytes: 40, tailWhenExceeded: true))->read($file);

    expect($content->truncated)->toBeTrue()
        ->and($content->position)->toBe('tail')
        ->and($content->bytesRead)->toBeLessThanOrEqual(40)
        ->and($content->content)->toEndWith('line 200')
        ->and($content->content)->toStartWith('line ');
});

it('can keep the head of large files instead', function () {
    $body = collect(range(1, 200))->map(fn (int $i): string => "line {$i}")->implode("\n");
    $path = $this->writeLog('single.log', $body);
    $file = LogFile::fromPath('single', 'Single', $path);

    $content = (new LogFileReader(maxBytes: 40, tailWhenExceeded: false))->read($file);

    expect($content->truncated)->toBeTrue()
        ->and($content->position)->toBe('head')
        ->and($content->content)->toStartWith('line 1');
});

it('leaves valid multibyte utf-8 untouched', function () {
    $path = $this->writeLog('single.log', "café ☕\nsecond");
    $file = LogFile::fromPath('single', 'Single', $path);

    expect((new LogFileReader)->read($file)->content)->toBe("café ☕\nsecond");
});

it('keeps a line with invalid utf-8 bytes readable', function () {
    // A raw latin-1 byte, as a binary payload or legacy input would produce.
    $path = $this->writeLog('single.log', "before\nbroken ".chr(0xE9)." byte\nafter");
    $file = LogFile::fromPath('single', 'Single', $path);

    $content = (new LogFileReader)->read($file);

    expect(mb_check_encoding($content->content, 'UTF-8'))->toBeTrue();

    // Blade escapes with htmlspecialchars(ENT_QUOTES), which returns an empty
    // string for an invalid sequence. Without sanitising, the whole line would
    // render blank rather than just the offending byte.
    $line = explode("\n", $content->content)[1];

    expect(e($line))->toContain('broken')->toContain('byte');
});
