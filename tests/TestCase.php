<?php

namespace JeffersonGoncalves\ReportUrl\Tests;

use JeffersonGoncalves\ReportUrl\ReportUrlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ReportUrlServiceProvider::class,
        ];
    }
}
