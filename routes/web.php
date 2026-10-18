<?php

use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\ReportUrl\Http\Controllers\DownloadReportUrlController;

Route::middleware([...config('report-url.route.middleware', ['web']), 'signed'])
    ->prefix(config('report-url.route.prefix', 'report-urls'))
    ->get('{reportUrl}', DownloadReportUrlController::class)
    ->name('report-url.download');
