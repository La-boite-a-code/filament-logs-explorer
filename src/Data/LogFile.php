<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Data;

/**
 * An immutable representation of a single log file on disk.
 *
 * The {@see self::id()} is a stable, opaque identifier derived from the file
 * path. It is used to reference a file from the front-end without ever exposing
 * (or accepting) a real filesystem path, which is a key part of the plugin's
 * path-traversal protection: the page only ever reads files that it resolved
 * itself and can match back by id.
 */
final class LogFile
{
    public function __construct(
        public readonly string $channel,
        public readonly string $channelLabel,
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly ?int $lastModifiedAt,
        public readonly bool $readable,
    ) {}

    /**
     * Build a log file from a path, reading its metadata from disk.
     */
    public static function fromPath(string $channel, string $channelLabel, string $path): self
    {
        $exists = is_file($path);

        return new self(
            channel: $channel,
            channelLabel: $channelLabel,
            path: $path,
            name: basename($path),
            size: $exists ? (int) @filesize($path) : 0,
            lastModifiedAt: $exists ? (@filemtime($path) ?: null) : null,
            readable: $exists && is_readable($path),
        );
    }

    /**
     * A stable, non-reversible identifier for this file.
     */
    public function id(): string
    {
        return hash('sha256', $this->path);
    }

    /**
     * @return array{id: string, channel: string, channelLabel: string, name: string, size: int, lastModifiedAt: int|null, readable: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'channel' => $this->channel,
            'channelLabel' => $this->channelLabel,
            'name' => $this->name,
            'size' => $this->size,
            'lastModifiedAt' => $this->lastModifiedAt,
            'readable' => $this->readable,
        ];
    }
}
