<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Exceptions\Halt;
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
            // Register the delete action as a modal action so the viewer's trash
            // button can mount it *stacked on top of* the open slide-over (nested
            // actions are resolved from their parent, not the page).
            ->registerModalActions([
                $this->deleteLogAction(),
            ])
            ->modalContent(fn (array $arguments): ?View => $this->viewerContent($arguments['file'] ?? ''));
    }

    /**
     * The confirmation action for deleting a log file. It is mounted both from
     * the file list and from the viewer toolbar with
     * `mountAction('deleteLog', { file: '<id>' })`. When it is confirmed from
     * within the viewer slide-over, `cancelParentActions()` makes it close the
     * slide-over too; cancelling it leaves the viewer open.
     */
    public function deleteLogAction(): Action
    {
        return Action::make('deleteLog')
            ->label(static::trans('viewer.delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-trash')
            ->modalHeading(static::trans('delete.modal_heading'))
            ->modalDescription(function (array $arguments): ?string {
                $file = $this->fileFor($arguments);

                return $file === null
                    ? null
                    : static::trans('delete.modal_description', ['name' => $file->name]);
            })
            ->modalSubmitActionLabel(static::trans('viewer.delete'))
            ->cancelParentActions()
            ->action(function (array $arguments): void {
                if (! $this->deleteFile($arguments['file'] ?? '')) {
                    throw new Halt;
                }
            });
    }

    public function downloadFile(string $id): ?BinaryFileResponse
    {
        $file = $this->repository()->find($id);

        if ($file === null || ! $file->readable) {
            return null;
        }

        return response()->download($file->path, $file->name);
    }

    /**
     * Permanently delete a log file from disk. The file is resolved back from
     * its opaque id (never a raw path), guaranteeing it belongs to the set the
     * repository resolved itself, and the deletion is re-authorized server-side.
     * Returns whether the file is gone afterwards, so {@see self::deleteLogAction()}
     * can keep the confirmation modal open when the deletion fails.
     */
    protected function deleteFile(string $id): bool
    {
        if (! $this->canDelete()) {
            return false;
        }

        $file = $this->repository()->find($id);

        if ($file === null) {
            return false;
        }

        if (is_file($file->path) && ! @unlink($file->path)) {
            Notification::make()
                ->danger()
                ->title(static::trans('delete.failed_title'))
                ->body(static::trans('delete.failed_body', ['name' => $file->name]))
                ->send();

            return false;
        }

        // Re-scan the disk so the list no longer shows the deleted file.
        $this->refreshLogs();

        Notification::make()
            ->success()
            ->title(static::trans('delete.success_title'))
            ->body(static::trans('delete.success_body', ['name' => $file->name]))
            ->send();

        return true;
    }

    public function canDelete(): bool
    {
        return static::plugin()?->canDelete() ?? false;
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
            'canDelete' => $this->canDelete(),
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
