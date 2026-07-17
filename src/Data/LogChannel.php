<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Data;

use Illuminate\Support\Collection;

/**
 * A logging channel together with the (most recent) log files resolved for it.
 */
final class LogChannel
{
    /**
     * @param  Collection<int, LogFile>  $files
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $driver,
        public readonly Collection $files,
    ) {}

    public function isEmpty(): bool
    {
        return $this->files->isEmpty();
    }

    public function count(): int
    {
        return $this->files->count();
    }
}
