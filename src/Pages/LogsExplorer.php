<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use LaBoiteACode\FilamentLogsExplorer\Data\LogChannel;
use LaBoiteACode\FilamentLogsExplorer\Data\LogFile;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerPlugin;
use LaBoiteACode\FilamentLogsExplorer\Support\LogChannelRepository;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use UnitEnum;

/**
 * @property-read Collection $channels
 */
class LogsExplorer extends Page
{
    protected string $view = 'filament-logs-explorer::pages.logs-explorer';

    protected ?LogChannelRepository $repositoryInstance = null;

    protected static function trans(string $key, array $replace = []): string
    {
        return (string) trans("filament-logs-explorer::filament-logs-explorer.{$key}", $replace);
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation (delegated to the plugin, which falls back to config)
    |--------------------------------------------------------------------------
    */

    public static function getNavigationLabel(): string
    {
        return static::plugin()?->getNavigationLabel()
            ?? static::trans('navigation.label');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::plugin()?->getNavigationIcon()
            ?? config('filament-logs-explorer.navigation.icon');
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::plugin()?->getActiveNavigationIcon()
            ?? config('filament-logs-explorer.navigation.active_icon')
            ?? static::getNavigationIcon();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return static::plugin()?->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return static::plugin()?->getNavigationSort();
    }

    public static function getNavigationParentItem(): ?string
    {
        return static::plugin()?->getNavigationParentItem();
    }

    public static function getNavigationBadge(): ?string
    {
        $plugin = static::plugin();

        if ($plugin === null || ! $plugin->hasNavigationBadge()) {
            return null;
        }

        $count = $plugin->makeChannelRepository()->channels()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::plugin()?->shouldRegisterNavigation() ?? true;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return static::plugin()?->getSlug() ?? 'logs';
    }

    public static function getCluster(): ?string
    {
        return static::plugin()?->getCluster() ?? parent::getCluster();
    }

    public static function canAccess(): bool
    {
        return static::plugin()?->canAccess() ?? true;
    }

    /*
    |--------------------------------------------------------------------------
    | Page chrome
    |--------------------------------------------------------------------------
    */

    public function getTitle(): string|Htmlable
    {
        return static::trans('page.title');
    }

    public function getHeading(): string|Htmlable|null
    {
        return static::trans('page.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return static::trans('page.subheading');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(static::trans('actions.refresh'))
                ->tooltip(static::trans('actions.refresh_tooltip'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshLogs()),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    /**
     * @return Collection<int, LogChannel>
     */
    #[Computed]
    public function channels(): Collection
    {
        return $this->repository()->channels();
    }

    public function previousFileId(string $id): ?string
    {
        $files = $this->repository()->files();
        $index = $files->search(fn (LogFile $file): bool => $file->id() === $id);

        if ($index === false || $index <= 0) {
            return null;
        }

        return $files->get($index - 1)?->id();
    }

    public function nextFileId(string $id): ?string
    {
        $files = $this->repository()->files();
        $index = $files->search(fn (LogFile $file): bool => $file->id() === $id);

        if ($index === false || $index >= ($files->count() - 1)) {
            return null;
        }

        return $files->get($index + 1)?->id();
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    /**
     * The slide-over that displays a single log file. It is mounted from the
     * file list with `mountAction('viewLog', { file: '<id>' })` and re-mounted
     * in place with `replaceMountedAction(...)` to move between files.
     */
    public function viewLogAction(): Action
    {
        return Action::make('viewLog')
            ->slideOver()
            ->modalWidth('7xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(static::trans('viewer.close'))
            ->modalHeading(fn (array $arguments): string => $this->fileFor($arguments)?->name
                ?? static::trans('viewer.title'))
            ->modalDescription(fn (array $arguments): ?string => $this->viewerDescription($arguments['file'] ?? ''))
            ->modalContent(fn (array $arguments): ?View => $this->viewerContent($arguments['file'] ?? ''));
    }

    public function downloadFile(string $id): ?BinaryFileResponse
    {
        $file = $this->repository()->find($id);

        if ($file === null || ! $file->readable) {
            return null;
        }

        return response()->download($file->path, $file->name);
    }

    public function refreshLogs(): void
    {
        $this->repositoryInstance = null;

        unset($this->channels);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function fileFor(array $arguments): ?LogFile
    {
        $id = $arguments['file'] ?? null;

        return is_string($id) ? $this->repository()->find($id) : null;
    }

    protected function viewerContent(string $id): ?View
    {
        $file = $this->repository()->find($id);

        if ($file === null) {
            return null;
        }

        return view('filament-logs-explorer::components.log-viewer', [
            'file' => $file,
            'content' => FilamentLogsExplorerPlugin::get()->makeReader()->read($file),
            'previousFileId' => $this->previousFileId($id),
            'nextFileId' => $this->nextFileId($id),
        ]);
    }

    protected function viewerDescription(string $id): ?string
    {
        $file = $this->repository()->find($id);

        if ($file === null) {
            return null;
        }

        $files = $this->repository()->files();
        $index = $files->search(fn (LogFile $item): bool => $item->id() === $id);

        $parts = [$file->channelLabel];

        if ($index !== false) {
            $parts[] = static::trans('viewer.position', [
                'current' => $index + 1,
                'total' => $files->count(),
            ]);
        }

        return implode(' · ', $parts);
    }

    protected function repository(): LogChannelRepository
    {
        return $this->repositoryInstance ??= FilamentLogsExplorerPlugin::get()->makeChannelRepository();
    }

    protected static function plugin(): ?FilamentLogsExplorerPlugin
    {
        try {
            return FilamentLogsExplorerPlugin::get();
        } catch (Throwable) {
            return null;
        }
    }
}
