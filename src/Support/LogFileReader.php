<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Support;

use LaBoiteACode\FilamentLogsExplorer\Data\LogFile;
use LaBoiteACode\FilamentLogsExplorer\Data\LogFileContent;

/**
 * Reads log file contents for the viewer.
 *
 * To keep the UI responsive (and to avoid loading gigabytes into memory) files
 * larger than {@see self::$maxBytes} are truncated. Depending on
 * {@see self::$tailWhenExceeded} we either keep the END of the file (the most
 * recent entries, which is usually what you want) or the beginning.
 */
class LogFileReader
{
    public function __construct(
        protected int $maxBytes = 5_242_880,
        protected bool $tailWhenExceeded = true,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            maxBytes: (int) config('filament-logs-explorer.reader.max_bytes', 5_242_880),
            tailWhenExceeded: (bool) config('filament-logs-explorer.reader.tail_when_exceeded', true),
        );
    }

    public function read(LogFile $file): LogFileContent
    {
        if (! $file->readable) {
            return LogFileContent::unreadable();
        }

        $total = $file->size;

        if ($total === 0) {
            return new LogFileContent(
                content: '',
                lineCount: 0,
                readable: true,
                truncated: false,
                position: 'full',
                bytesRead: 0,
                totalBytes: 0,
            );
        }

        if ($total <= $this->maxBytes) {
            $content = $this->normalize((string) @file_get_contents($file->path));

            return new LogFileContent(
                content: $content,
                lineCount: $this->countLines($content),
                readable: true,
                truncated: false,
                position: 'full',
                bytesRead: strlen($content),
                totalBytes: $total,
            );
        }

        if ($this->tailWhenExceeded) {
            $raw = $this->readTail($file->path, $this->maxBytes);
            $position = 'tail';
        } else {
            $raw = $this->readHead($file->path, $this->maxBytes);
            $position = 'head';
        }

        $content = $this->normalize($raw);

        return new LogFileContent(
            content: $content,
            lineCount: $this->countLines($content),
            readable: true,
            truncated: true,
            position: $position,
            bytesRead: strlen($raw),
            totalBytes: $total,
        );
    }

    protected function readTail(string $path, int $bytes): string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            fseek($handle, -$bytes, SEEK_END);
            $content = (string) fread($handle, $bytes);
        } finally {
            fclose($handle);
        }

        // Drop the (probably partial) first line so we never show a broken entry.
        $newline = strpos($content, "\n");

        if ($newline !== false) {
            $content = substr($content, $newline + 1);
        }

        return $content;
    }

    protected function readHead(string $path, int $bytes): string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            $content = (string) fread($handle, $bytes);
        } finally {
            fclose($handle);
        }

        // Drop the (probably partial) last line.
        $newline = strrpos($content, "\n");

        if ($newline !== false) {
            $content = substr($content, 0, $newline);
        }

        return $content;
    }

    /**
     * Normalise line endings and guarantee valid UTF-8.
     *
     * Log files carry whatever bytes the application wrote: a binary payload in
     * an exception dump, legacy latin-1 input, or a multibyte character cut in
     * half by the truncation window. Blade escapes with htmlspecialchars() using
     * ENT_QUOTES but not ENT_SUBSTITUTE, which returns an EMPTY string for an
     * invalid sequence, so a single bad byte would silently blank out the whole
     * line. Substituting U+FFFD keeps the line readable and makes the damaged
     * bytes visible instead.
     */
    protected function normalize(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if ($content === '' || mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        $substitute = mb_substitute_character();
        mb_substitute_character(0xFFFD);

        try {
            return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        } finally {
            mb_substitute_character($substitute);
        }
    }

    protected function countLines(string $content): int
    {
        if ($content === '') {
            return 0;
        }

        return substr_count($content, "\n") + 1;
    }
}
