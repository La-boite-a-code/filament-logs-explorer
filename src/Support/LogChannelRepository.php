<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaBoiteACode\FilamentLogsExplorer\Data\LogChannel;
use LaBoiteACode\FilamentLogsExplorer\Data\LogFile;

/**
 * Resolves the log files that should be displayed, grouped by logging channel.
 *
 * The source of truth is your config/logging.php file: for every configured
 * channel we resolve the concrete file(s) written by its driver
 * (single / daily / monolog stream, and the file based members of a stack).
 * Optionally, orphan *.log files sitting in the log directory can be surfaced
 * under a dedicated "untracked" channel.
 */
class LogChannelRepository
{
    /** @var Collection<int, LogChannel>|null */
    protected ?Collection $resolved = null;

    /**
     * @param  array<int, string>  $channels
     * @param  array<int, string>  $excludeChannels
     */
    public function __construct(
        protected array $channels = [],
        protected array $excludeChannels = [],
        protected bool $expandStacks = true,
        protected bool $discoverUntracked = false,
        protected ?string $logDirectory = null,
        protected ?string $untrackedLabel = null,
        protected int $filesPerChannel = 15,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            channels: (array) config('filament-logs-explorer.channels', []),
            excludeChannels: (array) config('filament-logs-explorer.exclude_channels', []),
            expandStacks: (bool) config('filament-logs-explorer.expand_stacks', true),
            discoverUntracked: (bool) config('filament-logs-explorer.discover_untracked_files', false),
            logDirectory: config('filament-logs-explorer.log_directory'),
            untrackedLabel: config('filament-logs-explorer.untracked_channel_label'),
            filesPerChannel: (int) config('filament-logs-explorer.files_per_channel', 15),
        );
    }

    /**
     * All channels that resolved to at least one readable file, in display order.
     *
     * @return Collection<int, LogChannel>
     */
    public function channels(): Collection
    {
        return $this->resolved ??= $this->resolve();
    }

    /**
     * A flat, ordered list of every file across all channels. Used to power the
     * "previous / next file" navigation inside the viewer.
     *
     * @return Collection<int, LogFile>
     */
    public function files(): Collection
    {
        return $this->channels()
            ->flatMap(fn (LogChannel $channel): Collection => $channel->files)
            ->values();
    }

    /**
     * Resolve a file back from its opaque id, guaranteeing it belongs to the
     * allowed set (never trust a raw path coming from the front-end).
     */
    public function find(string $id): ?LogFile
    {
        return $this->files()->first(fn (LogFile $file): bool => $file->id() === $id);
    }

    /**
     * Forget the memoised result so the next call re-scans the disk.
     */
    public function flush(): self
    {
        $this->resolved = null;

        return $this;
    }

    /**
     * @return Collection<int, LogChannel>
     */
    protected function resolve(): Collection
    {
        $loggingChannels = (array) config('logging.channels', []);
        $channels = collect();

        /** @var array<string, true> $processedChannels */
        $processedChannels = [];
        /** @var array<string, true> $seenPaths */
        $seenPaths = [];

        $queue = $this->channelNames($loggingChannels);

        while ($queue !== []) {
            $name = array_shift($queue);

            if (isset($processedChannels[$name]) || in_array($name, $this->excludeChannels, true)) {
                continue;
            }

            $processedChannels[$name] = true;

            $config = $loggingChannels[$name] ?? null;

            if (! is_array($config)) {
                continue;
            }

            $driver = $config['driver'] ?? null;

            // A stack has no files of its own: expand it into its members.
            if ($driver === 'stack' && $this->expandStacks) {
                foreach ((array) ($config['channels'] ?? []) as $member) {
                    if (is_string($member) && ! isset($processedChannels[$member])) {
                        $queue[] = $member;
                    }
                }

                continue;
            }

            $files = $this->filesForChannel($name, $config, $seenPaths);

            if ($files->isNotEmpty()) {
                $channels->push(new LogChannel(
                    name: $name,
                    label: $this->channelLabel($name),
                    driver: is_string($driver) ? $driver : null,
                    files: $files,
                ));
            }
        }

        if ($this->discoverUntracked) {
            $untracked = $this->untrackedFiles($seenPaths);

            if ($untracked->isNotEmpty()) {
                $channels->push(new LogChannel(
                    name: '__untracked',
                    label: $this->untrackedLabel(),
                    driver: null,
                    files: $untracked,
                ));
            }
        }

        return $channels->values();
    }

    /**
     * The ordered list of channel names to inspect.
     *
     * @param  array<string, mixed>  $loggingChannels
     * @return array<int, string>
     */
    protected function channelNames(array $loggingChannels): array
    {
        if ($this->channels !== []) {
            return array_values(array_filter($this->channels, 'is_string'));
        }

        return array_values(array_filter(array_keys($loggingChannels), 'is_string'));
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, true>  $seenPaths
     * @return Collection<int, LogFile>
     */
    protected function filesForChannel(string $name, array $config, array &$seenPaths): Collection
    {
        $paths = $this->pathsForChannel($config);

        $files = collect();

        foreach ($paths as $path) {
            $real = realpath($path) ?: $path;

            if (isset($seenPaths[$real])) {
                continue;
            }

            $seenPaths[$real] = true;

            $files->push(LogFile::fromPath($name, $this->channelLabel($name), $real));
        }

        return $this->sortAndLimit($files);
    }

    /**
     * Resolve the concrete, existing file paths for a single (non-stack) channel.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    protected function pathsForChannel(array $config): array
    {
        $driver = $config['driver'] ?? null;

        return match ($driver) {
            'single' => $this->singlePaths($config),
            'daily' => $this->dailyPaths($config),
            'monolog' => $this->monologPaths($config),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    protected function singlePaths(array $config): array
    {
        $path = $this->configuredPath($config);

        return is_file($path) ? [$path] : [];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    protected function dailyPaths(array $config): array
    {
        $path = $this->configuredPath($config);
        $directory = dirname($path);
        $basename = basename($path);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $stem = $extension !== ''
            ? substr($basename, 0, -(strlen($extension) + 1))
            : $basename;

        $pattern = $directory.DIRECTORY_SEPARATOR.$stem.'-*'.($extension !== '' ? '.'.$extension : '');

        return array_values(array_filter((array) glob($pattern), 'is_file'));
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    protected function monologPaths(array $config): array
    {
        $stream = $config['with']['stream']
            ?? $config['handler_with']['stream']
            ?? null;

        if (! is_string($stream) || str_starts_with($stream, 'php://') || ! is_file($stream)) {
            return [];
        }

        return [$stream];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function configuredPath(array $config): string
    {
        $path = $config['path'] ?? null;

        return is_string($path) && $path !== ''
            ? $path
            : storage_path('logs/laravel.log');
    }

    /**
     * @param  array<string, true>  $seenPaths
     *
     * @param-out array<array-key, true>  $seenPaths
     *
     * @return Collection<int, LogFile>
     */
    protected function untrackedFiles(array &$seenPaths): Collection
    {
        $directory = $this->logDirectory ?: storage_path('logs');

        $files = collect();

        foreach ((array) glob(rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.log') as $path) {
            if (! is_file($path)) {
                continue;
            }

            $real = realpath($path) ?: $path;

            if (isset($seenPaths[$real])) {
                continue;
            }

            $seenPaths[$real] = true;

            $files->push(LogFile::fromPath('__untracked', $this->untrackedLabel(), $real));
        }

        return $this->sortAndLimit($files);
    }

    /**
     * @param  Collection<int, LogFile>  $files
     * @return Collection<int, LogFile>
     */
    protected function sortAndLimit(Collection $files): Collection
    {
        $sorted = $files
            ->sortByDesc(fn (LogFile $file): int => $file->lastModifiedAt ?? 0)
            ->values();

        if ($this->filesPerChannel > 0) {
            $sorted = $sorted->take($this->filesPerChannel)->values();
        }

        return $sorted;
    }

    protected function channelLabel(string $name): string
    {
        return (string) Str::of($name)->replace(['-', '_'], ' ')->headline();
    }

    protected function untrackedLabel(): string
    {
        if (is_string($this->untrackedLabel) && $this->untrackedLabel !== '') {
            return $this->untrackedLabel;
        }

        return (string) trans('filament-logs-explorer::filament-logs-explorer.channels.untracked');
    }
}
