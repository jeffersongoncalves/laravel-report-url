<?php

return [
    'table' => 'report_urls',

    // Where the files live. Use a private disk: the files are only meant to be reached through the signed link.
    'disk' => env('REPORT_URL_DISK', 'local'),

    'directory' => 'report-urls',

    // Minutes a link stays valid (7 days). Null = the link never expires.
    'ttl' => 60 * 24 * 7,

    'route' => [
        'prefix' => 'report-urls',
        'middleware' => ['web'],
    ],

    // The downloader must be logged in as the file's owner (when the file has one).
    'restrict_to_owner' => true,

    'integrations' => [
        // Bind laravel-saved-filter's HostsReportFiles when that package is installed,
        // so large scheduled reports are emailed as download links.
        'saved_filter' => true,
    ],
];
