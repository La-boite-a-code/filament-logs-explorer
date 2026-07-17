<x-filament-panels::page>
    @php($canDelete = $this->canDelete())

    <div class="lge-page">
        @forelse ($this->channels as $channel)
            <x-filament::section
                :collapsible="true"
                :collapsed="false"
            >
                <x-slot name="heading">
                    <span class="lge-channel-heading">
                        {{ $channel->label }}
                        <x-filament::badge color="gray" size="sm">
                            {{ trans_choice('filament-logs-explorer::filament-logs-explorer.channels.file_count', $channel->count(), ['count' => $channel->count()]) }}
                        </x-filament::badge>
                    </span>
                </x-slot>

                @if ($channel->driver)
                    <x-slot name="description">
                        <code class="lge-driver">{{ $channel->driver }}</code>
                    </x-slot>
                @endif

                <ul role="list" class="lge-file-list">
                    @foreach ($channel->files as $file)
                        <li class="lge-file-row">
                            <button
                                type="button"
                                @disabled(! $file->readable)
                                wire:click="mountAction('viewLog', { file: '{{ $file->id() }}' })"
                                wire:loading.attr="disabled"
                                wire:target="mountAction"
                                class="lge-file-open"
                            >
                                <x-filament::icon
                                    icon="heroicon-o-document-text"
                                    class="lge-file-icon"
                                />

                                <span class="lge-file-body">
                                    <span class="lge-file-name">{{ $file->name }}</span>
                                    <span class="lge-file-meta">
                                        @if ($file->readable)
                                            <span>{{ \Illuminate\Support\Number::fileSize($file->size, precision: 1) }}</span>
                                            @if ($file->lastModifiedAt)
                                                <span aria-hidden="true">·</span>
                                                <span title="{{ \Illuminate\Support\Carbon::createFromTimestamp($file->lastModifiedAt)->toDateTimeString() }}">
                                                    {{ __('filament-logs-explorer::filament-logs-explorer.list.modified', ['time' => \Illuminate\Support\Carbon::createFromTimestamp($file->lastModifiedAt)->diffForHumans()]) }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="lge-file-warning">{{ __('filament-logs-explorer::filament-logs-explorer.list.unreadable') }}</span>
                                        @endif
                                    </span>
                                </span>

                                @if ($file->readable)
                                    <x-filament::icon
                                        icon="heroicon-o-chevron-right"
                                        class="lge-file-chevron"
                                    />
                                @endif
                            </button>

                            @if ($canDelete)
                                <x-filament::icon-button
                                    icon="heroicon-o-trash"
                                    :label="__('filament-logs-explorer::filament-logs-explorer.viewer.delete')"
                                    :tooltip="__('filament-logs-explorer::filament-logs-explorer.viewer.delete')"
                                    color="danger"
                                    class="lge-file-delete"
                                    wire:click="mountAction('deleteLog', { file: '{{ $file->id() }}' })"
                                    wire:loading.attr="disabled"
                                />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @empty
            <x-filament::section>
                <div class="lge-empty-state">
                    <x-filament::icon
                        icon="heroicon-o-document-magnifying-glass"
                        class="lge-empty-state-icon"
                    />
                    <h3 class="lge-empty-state-heading">
                        {{ __('filament-logs-explorer::filament-logs-explorer.list.empty.heading') }}
                    </h3>
                    <p class="lge-empty-state-description">
                        {{ __('filament-logs-explorer::filament-logs-explorer.list.empty.description') }}
                    </p>
                </div>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
