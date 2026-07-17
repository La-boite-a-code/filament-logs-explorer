<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentLogsExplorer\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use LaBoiteACode\FilamentLogsExplorer\FilamentLogsExplorerServiceProvider;
use LaBoiteACode\FilamentLogsExplorer\Tests\Fixtures\AdminPanelProvider;
use LaBoiteACode\FilamentLogsExplorer\Tests\Fixtures\ForceFullRenderHook;
use LaBoiteACode\FilamentLogsExplorer\Tests\Fixtures\User;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Monolog\Handler\StreamHandler;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // In the isolated Livewire test harness the DataStore mechanism is not
        // always resolved as a shared instance, which breaks error-bag state
        // during render. Pin it as a singleton so state persists as it does
        // over HTTP. (Only needed for these component tests.)
        app()->singleton(DataStore::class);

        // Livewire normally flushes its per-request state between HTTP requests.
        // The isolated test harness does not, so Filament's partial rendering
        // leaks between tests and a second rendered modal comes back empty.
        // Flushing what we can and forcing full renders keeps things isolated.
        Livewire::flushState();
        View::flushState();
        Livewire::componentHook(ForceFullRenderHook::class);

        File::ensureDirectoryExists($this->logsPath());
        File::cleanDirectory($this->logsPath());

        $this->actingAs(new User);

        filament()->setCurrentPanel('admin');

        // Livewire component tests do not run the HTTP middleware stack, so the
        // "errors" view bag that ShareErrorsFromSession normally provides is
        // never shared. Filament pages rely on it during render.
        View::share('errors', new ViewErrorBag);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->logsPath());

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            // Livewire and the Blade icon providers must boot before Filament,
            // which depends on them.
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentLogsExplorerServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $logs = $this->logsPath();

        $app['config']->set('logging.channels', [
            'single' => [
                'driver' => 'single',
                'path' => $logs.'/single.log',
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => $logs.'/daily.log',
                'days' => 14,
            ],
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single', 'daily'],
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => 'https://example.test/hook',
            ],
            'stderr' => [
                'driver' => 'monolog',
                'handler' => StreamHandler::class,
                'with' => ['stream' => 'php://stderr'],
            ],
        ]);
    }

    /**
     * The temporary directory used to hold fixture log files during a test run.
     */
    protected function logsPath(): string
    {
        return sys_get_temp_dir().'/filament-logs-explorer-tests/logs';
    }

    /**
     * Write a fixture log file and return its absolute path.
     */
    protected function writeLog(string $name, string $contents = "log line\n"): string
    {
        $path = $this->logsPath().'/'.$name;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);

        return $path;
    }
}
