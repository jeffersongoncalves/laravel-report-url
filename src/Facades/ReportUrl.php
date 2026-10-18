<?php

namespace JeffersonGoncalves\ReportUrl\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\ReportUrl\ReportUrl
 */
class ReportUrl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-report-url';
    }
}
