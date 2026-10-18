<?php

namespace JeffersonGoncalves\ReportUrl;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ReportUrlServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-report-url')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
