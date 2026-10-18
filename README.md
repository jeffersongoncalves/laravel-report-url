<div class="filament-hidden">

![Laravel Report Url](https://raw.githubusercontent.com/jeffersongoncalves/laravel-report-url/main/art/jeffersongoncalves-laravel-report-url.png)

</div>

# Laravel Report Url

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-report-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-report-url)
[![PHP Version](https://img.shields.io/packagist/dependency-v/jeffersongoncalves/laravel-report-url/php.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-report-url)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-report-url/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-report-url/actions?query=workflow%3ATests+branch%3Amain)
[![Code Style](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-report-url/pint.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-report-url/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-report-url.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-report-url)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-report-url.svg?style=flat-square)](LICENSE.md)

Hand out **temporary download links** for files your app generates — reports, exports, backups — instead of attaching them to an email. Mail providers cap messages at 10–25 MB; a link has no such limit.

Each file is kept on a private disk and reached through a **signed, expiring URL**. Optionally only its owner can download it. Downloads are counted, and expired files are pruned every day.

Works on its own, and plugs into [laravel-saved-filter](https://github.com/jeffersongoncalves/laravel-saved-filter): install both and scheduled reports too large to attach are emailed as links.

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/laravel-report-url
php artisan vendor:publish --tag="report-url-migrations"
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="report-url-config"
```

```php
return [
    'table' => 'report_urls',
    'disk' => env('REPORT_URL_DISK', 'local'),     // where the files live; use a private disk
    'directory' => 'report-urls',
    'ttl' => 60 * 24 * 7,                          // minutes a link stays valid (7 days); null = never expires
    'route' => ['prefix' => 'report-urls', 'middleware' => ['web']],
    'restrict_to_owner' => true,                   // the downloader must be logged in as the file's owner
    'integrations' => ['saved_filter' => true],    // bind HostsReportFiles when laravel-saved-filter is installed
];
```

## Usage

```php
use JeffersonGoncalves\ReportUrl\Facades\ReportUrl;

// From a string
$report = ReportUrl::store($csv, 'sales-2026-10.csv', owner: $user);

// From a local file, streamed (not loaded into memory)
$report = ReportUrl::storeFile(storage_path('app/tmp/export.xlsx'), 'export.xlsx', $user, ttlMinutes: 60);

$report->url();        // signed download link
$report->expires_at;   // Carbon, or null when the ttl is null
```

Both methods write the file to `{directory}/{uuid}/{fileName}` on the configured disk and return the `JeffersonGoncalves\ReportUrl\Models\ReportUrl` model. The owner is optional; without one, anyone holding the link can download the file until it expires.

The model keeps `downloads` and `last_downloaded_at`, and each download fires `JeffersonGoncalves\ReportUrl\Events\ReportUrlDownloaded` (`$reportUrl`, `$user`) for audit logs.

## Security model

- **Signed and expiring.** The link is a `URL::temporarySignedRoute()`: it can't be guessed or tampered with (403) and stops working at `expires_at`. Moving `expires_at` earlier on the record also revokes an already sent link (410 Gone).
- **Owner check.** With `restrict_to_owner` on and an owner set, a guest is redirected to the `login` route (403 when there is none) and any other user gets 403.
- **Private disk.** The file is only served through the route. Keep `disk` private (the default `local` disk is).
- **File names are never paths.** Only the base name of `$fileName` is used.

## Pruning

```bash
php artisan report-url:prune            # delete expired links and their files
php artisan report-url:prune --dry-run  # only count them
```

The command is scheduled daily with `withoutOverlapping()->onOneServer()`, so it only needs the Laravel scheduler running. `onOneServer` needs a cache driver with locks (redis, database, memcached...) when several servers run the scheduler. Links without an expiry are never pruned.

## laravel-saved-filter

Install [jeffersongoncalves/laravel-saved-filter](https://github.com/jeffersongoncalves/laravel-saved-filter) (1.9+) next to this package and nothing else is needed: large scheduled reports are emailed as links.

When a report is bigger than `saved-filter.reports_max_attachment_size`, laravel-saved-filter asks the bound `HostsReportFiles` for a link. This package binds `SavedFilterReportHost`, which stores the file owned by the recipient and returns its URL and expiry; the email then shows a **Download the report** button and the expiry date.

Your own `HostsReportFiles` binding is never overwritten. Set `integrations.saved_filter` to `false` to skip the binding.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
