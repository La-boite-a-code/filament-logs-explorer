<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Data;

/**
 * The (possibly truncated) content of a log file, ready to be rendered in the
 * viewer.
 */
final class LogFileContent
{
    public function __construct(
        public readonly string $content,
        public readonly int $lineCount,
        public readonly bool $readable,
        public readonly bool $truncated,
        /** One of: "full", "head", "tail". */
        public readonly string $position,
        public readonly int $bytesRead,
        public readonly int $totalBytes,
    ) {}

    public static function unreadable(): self
    {
        return new self(
            content: '',
            lineCount: 0,
            readable: false,
            truncated: false,
            position: 'full',
            bytesRead: 0,
            totalBytes: 0,
        );
    }

    public function isEmpty(): bool
    {
        return $this->readable && $this->content === '';
    }

    public function isTail(): bool
    {
        return $this->position === 'tail';
    }
}
