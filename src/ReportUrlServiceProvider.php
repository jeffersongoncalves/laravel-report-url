<?php

namespace JeffersonGoncalves\ReportUrl;

use Illuminate\Console\Scheduling\Schedule;
use JeffersonGoncalves\ReportUrl\Commands\PruneCommand;
use JeffersonGoncalves\ReportUrl\Integrations\SavedFilterReportHost;
use JeffersonGoncalves\SavedFilter\Contracts\HostsReportFiles;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ReportUrlServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-report-url')
            ->hasConfigFile('report-url')
            ->hasMigrations(['create_report_urls_table'])
            ->hasRoute('web')
            ->hasCommand(PruneCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ReportUrlManager::class);

        // Only when laravel-saved-filter is installed, and never over the app's own binding.
        if (config('report-url.integrations.saved_filter', true)
            && interface_exists(HostsReportFiles::class)
            && ! $this->app->bound(HostsReportFiles::class)) {
            $this->app->bind(HostsReportFiles::class, SavedFilterReportHost::class);
        }
    }

    public function packageBooted(): void
    {
        // onOneServer needs a cache driver with locks (redis, database, memcached...) when several servers run the scheduler.
        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule
            ->command(PruneCommand::class)
            ->daily()
            ->withoutOverlapping()
            ->onOneServer());
    }
}
