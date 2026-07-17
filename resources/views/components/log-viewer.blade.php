<div
    wire:key="lge-viewer-{{ $file->id() }}"
    x-data="logsExplorerViewer()"
    @keydown="onKeydown($event)"
    class="lge-root"
>
    @php
        /** @var \LaBoiteACode\FilamentLogsExplorer\Data\LogFile $file */
        /** @var \LaBoiteACode\FilamentLogsExplorer\Data\LogFileContent $content */
        /** @var string|null $previousFileId */
        /** @var string|null $nextFileId */
        $lines = $content->content === '' ? [] : explode("\n", $content->content);
    @endphp

    <div class="lge-toolbar">
        <div class="lge-toolbar-group">
            <x-filament::icon-button
                icon="heroicon-o-chevron-left"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.previous_file')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.previous_file')"
                color="gray"
                wire:click="replaceMountedAction('viewLog', { file: '{{ $previousFileId }}' })"
                wire:loading.attr="disabled"
                :disabled="blank($previousFileId)"
            />
            <x-filament::icon-button
                icon="heroicon-o-chevron-right"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.next_file')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.next_file')"
                color="gray"
                wire:click="replaceMountedAction('viewLog', { file: '{{ $nextFileId }}' })"
                wire:loading.attr="disabled"
                :disabled="blank($nextFileId)"
            />
        </div>

        <div class="lge-toolbar-group lge-toolbar-search">
            <div class="lge-search-wrap">
                <x-filament::icon icon="heroicon-m-magnifying-glass" class="lge-search-icon" />
                <input
                    type="search"
                    x-ref="search"
                    x-model.debounce.150ms="query"
                    @keydown.enter.prevent="$event.shiftKey ? previousMatch() : nextMatch()"
                    @keydown.escape.prevent.stop="clear()"
                    class="lge-search-input"
                    placeholder="{{ __('filament-logs-explorer::filament-logs-explorer.viewer.search_placeholder') }}"
                    autocomplete="off"
                    spellcheck="false"
                />
            </div>

            <span class="lge-matches" x-cloak x-show="query.trim().length">
                <span x-show="matches.length" x-text="matchLabel()"></span>
                <span x-show="! matches.length">{{ __('filament-logs-explorer::filament-logs-explorer.viewer.no_matches') }}</span>
            </span>

            <x-filament::icon-button
                icon="heroicon-o-chevron-up"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.previous_match')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.previous_match')"
                color="gray"
                x-on:click="previousMatch()"
            />
            <x-filament::icon-button
                icon="heroicon-o-chevron-down"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.next_match')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.next_match')"
                color="gray"
                x-on:click="nextMatch()"
            />
        </div>

        <div class="lge-toolbar-group">
            <x-filament::icon-button
                icon="heroicon-o-bars-arrow-up"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.go_to_top')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.go_to_top')"
                color="gray"
                x-on:click="scrollToTop()"
            />
            <x-filament::icon-button
                icon="heroicon-o-bars-arrow-down"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.go_to_bottom')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.go_to_bottom')"
                color="gray"
                x-on:click="scrollToBottom()"
            />
            <x-filament::icon-button
                icon="heroicon-o-arrow-down-tray"
                :label="__('filament-logs-explorer::filament-logs-explorer.viewer.download')"
                :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.download')"
                color="gray"
                wire:click="downloadFile('{{ $file->id() }}')"
                wire:loading.attr="disabled"
            />
        </div>
    </div>

    @if ($content->truncated)
        <p class="lge-banner">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="lge-banner-icon" />
            <span>
                {{ __('filament-logs-explorer::filament-logs-explorer.viewer.truncated_' . ($content->position === 'head' ? 'head' : 'tail'), [
                    'size' => \Illuminate\Support\Number::fileSize($content->bytesRead, precision: 1),
                    'lines' => trans_choice('filament-logs-explorer::filament-logs-explorer.viewer.lines', $content->lineCount, ['count' => number_format($content->lineCount)]),
                ]) }}
            </span>
        </p>
    @endif

    @if (! $content->readable)
        <div class="lge-state lge-state--warning">
            <x-filament::icon icon="heroicon-o-lock-closed" class="lge-state-icon" />
            <span>{{ __('filament-logs-explorer::filament-logs-explorer.viewer.unreadable') }}</span>
        </div>
    @elseif ($content->isEmpty())
        <div class="lge-state">
            <x-filament::icon icon="heroicon-o-inbox" class="lge-state-icon" />
            <span>{{ __('filament-logs-explorer::filament-logs-explorer.viewer.empty') }}</span>
        </div>
    @else
        <div class="lge-viewer" x-ref="viewer" tabindex="0">
            <div class="lge-lines">
                @foreach ($lines as $line)
                    <div class="lge-line" data-log-line>{{ $line }}</div>
                @endforeach
            </div>
        </div>

        <div class="lge-footer">
            <span>{{ trans_choice('filament-logs-explorer::filament-logs-explorer.viewer.lines', $content->lineCount, ['count' => number_format($content->lineCount)]) }}</span>
            <span class="lge-hint">{{ __('filament-logs-explorer::filament-logs-explorer.viewer.keyboard_hint') }}</span>
        </div>
    @endif
</div>
