<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer;

use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer;
use LaBoiteACode\FilamentLogsExplorer\Support\LogChannelRepository;
use LaBoiteACode\FilamentLogsExplorer\Support\LogFileReader;
use UnitEnum;

/**
 * The Filament plugin entry point.
 *
 * Everything is configurable, either globally through the published config file
 * or per-panel through this fluent API:
 *
 *     use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;
 *
 *     $panel->plugin(
 *         FilamentLogsExplorerPlugin::make()
 *             ->navigationGroup('System')
 *             ->navigationIcon('heroicon-o-bug-ant')
 *             ->navigationSort(99)
 *             ->channels(['daily', 'single'])
 *             ->filesPerChannel(20)
 *             ->canAccessUsing(fn (): bool => auth()->user()?->can('viewLogs') ?? false),
 *     );
 */
class FilamentLogsExplorerPlugin implements Plugin
{
    protected string|BackedEnum|null $navigationIcon = null;

    protected string|BackedEnum|null $activeNavigationIcon = null;

    protected string|UnitEnum|null $navigationGroup = null;

    protected ?string $navigationLabel = null;

    protected ?int $navigationSort = null;

    protected ?string $navigationParentItem = null;

    protected ?bool $navigationBadge = null;

    protected ?bool $registerNavigation = null;

    protected ?string $slug = null;

    protected ?string $cluster = null;

    /** @var array<int, string>|null */
    protected ?array $channels = null;

    /** @var array<int, string>|null */
    protected ?array $excludeChannels = null;

    protected ?bool $expandStacks = null;

    protected ?bool $discoverUntrackedFiles = null;

    protected ?string $logDirectory = null;

    protected ?int $filesPerChannel = null;

    protected ?int $maxBytes = null;

    protected ?bool $tailWhenExceeded = null;

    protected ?Closure $canAccessUsing = null;

    public function getId(): string
    {
        return 'filament-logs-explorer';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            LogsExplorer::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent configuration
    |--------------------------------------------------------------------------
    */

    public function navigationIcon(string|BackedEnum|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function activeNavigationIcon(string|BackedEnum|null $icon): static
    {
        $this->activeNavigationIcon = $icon;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationLabel(?string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function navigationParentItem(?string $item): static
    {
        $this->navigationParentItem = $item;

        return $this;
    }

    public function navigationBadge(bool $condition = true): static
    {
        $this->navigationBadge = $condition;

        return $this;
    }

    public function registerNavigation(bool $condition = true): static
    {
        $this->registerNavigation = $condition;

        return $this;
    }

    public function slug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function cluster(?string $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
    }

    /**
     * @param  array<int, string>|null  $channels
     */
    public function channels(?array $channels): static
    {
        $this->channels = $channels;

        return $this;
    }

    /**
     * @param  array<int, string>  $channels
     */
    public function excludeChannels(array $channels): static
    {
        $this->excludeChannels = $channels;

        return $this;
    }

    public function expandStacks(bool $condition = true): static
    {
        $this->expandStacks = $condition;

        return $this;
    }

    public function discoverUntrackedFiles(bool $condition = true, ?string $directory = null): static
    {
        $this->discoverUntrackedFiles = $condition;
        $this->logDirectory = $directory ?? $this->logDirectory;

        return $this;
    }

    public function logDirectory(?string $directory): static
    {
        $this->logDirectory = $directory;

        return $this;
    }

    public function filesPerChannel(int $count): static
    {
        $this->filesPerChannel = $count;

        return $this;
    }

    public function maxBytes(int $bytes): static
    {
        $this->maxBytes = $bytes;

        return $this;
    }

    public function tailWhenExceeded(bool $condition = true): static
    {
        $this->tailWhenExceeded = $condition;

        return $this;
    }

    public function canAccessUsing(Closure $callback): static
    {
        $this->canAccessUsing = $callback;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolved values (fluent API overrides config, config provides defaults)
    |--------------------------------------------------------------------------
    */

    public function getNavigationIcon(): string|BackedEnum|null
    {
        return $this->navigationIcon ?? config('filament-logs-explorer.navigation.icon');
    }

    public function getActiveNavigationIcon(): string|BackedEnum|null
    {
        return $this->activeNavigationIcon ?? config('filament-logs-explorer.navigation.active_icon');
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return $this->navigationGroup ?? config('filament-logs-explorer.navigation.group');
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel
            ?? config('filament-logs-explorer.navigation.label')
            ?? (string) trans('filament-logs-explorer::filament-logs-explorer.navigation.label');
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort ?? config('filament-logs-explorer.navigation.sort');
    }

    public function getNavigationParentItem(): ?string
    {
        return $this->navigationParentItem ?? config('filament-logs-explorer.navigation.parent_item');
    }

    public function hasNavigationBadge(): bool
    {
        return $this->navigationBadge ?? (bool) config('filament-logs-explorer.navigation.badge', false);
    }

    public function shouldRegisterNavigation(): bool
    {
        return $this->registerNavigation ?? (bool) config('filament-logs-explorer.navigation.register', true);
    }

    public function getSlug(): string
    {
        return $this->slug
            ?? config('filament-logs-explorer.slug')
            ?? 'logs';
    }

    public function getCluster(): ?string
    {
        return $this->cluster ?? config('filament-logs-explorer.cluster');
    }

    /**
     * @return array<int, string>
     */
    public function getChannels(): array
    {
        return $this->channels ?? (array) config('filament-logs-explorer.channels', []);
    }

    /**
     * @return array<int, string>
     */
    public function getExcludeChannels(): array
    {
        return $this->excludeChannels ?? (array) config('filament-logs-explorer.exclude_channels', []);
    }

    public function shouldExpandStacks(): bool
    {
        return $this->expandStacks ?? (bool) config('filament-logs-explorer.expand_stacks', true);
    }

    public function shouldDiscoverUntrackedFiles(): bool
    {
        return $this->discoverUntrackedFiles ?? (bool) config('filament-logs-explorer.discover_untracked_files', false);
    }

    public function getLogDirectory(): ?string
    {
        return $this->logDirectory ?? config('filament-logs-explorer.log_directory');
    }

    public function getFilesPerChannel(): int
    {
        return $this->filesPerChannel ?? (int) config('filament-logs-explorer.files_per_channel', 15);
    }

    public function getMaxBytes(): int
    {
        return $this->maxBytes ?? (int) config('filament-logs-explorer.reader.max_bytes', 5_242_880);
    }

    public function shouldTailWhenExceeded(): bool
    {
        return $this->tailWhenExceeded ?? (bool) config('filament-logs-explorer.reader.tail_when_exceeded', true);
    }

    public function canAccess(): bool
    {
        if ($this->canAccessUsing instanceof Closure) {
            return (bool) call_user_func($this->canAccessUsing);
        }

        $gate = config('filament-logs-explorer.authorization.gate');

        if (is_string($gate) && $gate !== '') {
            return Gate::allows($gate) === true;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Factories
    |--------------------------------------------------------------------------
    */

    public function makeChannelRepository(): LogChannelRepository
    {
        return new LogChannelRepository(
            channels: $this->getChannels(),
            excludeChannels: $this->getExcludeChannels(),
            expandStacks: $this->shouldExpandStacks(),
            discoverUntracked: $this->shouldDiscoverUntrackedFiles(),
            logDirectory: $this->getLogDirectory(),
            untrackedLabel: config('filament-logs-explorer.untracked_channel_label'),
            filesPerChannel: $this->getFilesPerChannel(),
        );
    }

    public function makeReader(): LogFileReader
    {
        return new LogFileReader(
            maxBytes: $this->getMaxBytes(),
            tailWhenExceeded: $this->shouldTailWhenExceeded(),
        );
    }
}
