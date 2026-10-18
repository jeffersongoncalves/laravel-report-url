## Laravel Report Url

Temporary, signed, expiring download links for generated files (reports, exports), with an optional owner check, download tracking and daily pruning.

### Installation

@verbatim
<code-snippet name="Install" lang="bash">
composer require jeffersongoncalves/laravel-report-url
php artisan vendor:publish --tag="report-url-migrations"
php artisan migrate
</code-snippet>
@endverbatim

### Usage

@verbatim
<code-snippet name="Store a file and get its link" lang="php">
use JeffersonGoncalves\ReportUrl\Facades\ReportUrl;

$report = ReportUrl::store($content, 'report.csv', $user);           // or storeFile($localPath, ...) to stream
$report = ReportUrl::store($content, 'report.csv', $user, 60);       // ttl in minutes, overrides config('report-url.ttl')

$report->url();       // URL::temporarySignedRoute('report-url.download', ...)
$report->expires_at;  // null = never expires
</code-snippet>
@endverbatim

### Rules
- Files go to `{directory}/{uuid}/{basename($fileName)}` on `config('report-url.disk')`; keep that disk private.
- `owner_type`/`owner_id` are nullable strings (int, UUID or ULID keys). With `restrict_to_owner` on and an owner set, only that user downloads (guest → `login` redirect or 403, other user → 403); without an owner anyone with the link can.
- The route `report-url.download` uses the configured middleware plus `signed`; a past `expires_at` on the record returns 410.
- Delete through the model (`$report->delete()`, `report-url:prune`): the `deleting` hook removes the file. A query-builder delete leaves the file behind.
- `report-url:prune` (scheduled daily, `onOneServer`) deletes expired rows; `--dry-run` only counts.
- `ReportUrlDownloaded` fires on each download (`$reportUrl`, `$user`).
- With laravel-saved-filter installed, `HostsReportFiles` is bound to `SavedFilterReportHost` unless the app bound its own or `integrations.saved_filter` is false.
