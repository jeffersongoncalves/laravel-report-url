<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ReportUrl\Events\ReportUrlDownloaded;
use JeffersonGoncalves\ReportUrl\Facades\ReportUrl;
use JeffersonGoncalves\ReportUrl\Integrations\SavedFilterReportHost;
use JeffersonGoncalves\ReportUrl\Models\ReportUrl as ReportUrlModel;
use JeffersonGoncalves\ReportUrl\ReportUrlServiceProvider;
use JeffersonGoncalves\SavedFilter\Contracts\HostsReportFiles;
use JeffersonGoncalves\SavedFilter\ReportFile;
use JeffersonGoncalves\SavedFilter\ReportLink;

class TestUser extends User
{
    protected $table = 'users';

    protected $guarded = [];
}

function user(string $name = 'Ana'): TestUser
{
    return TestUser::query()->create(['name' => $name]);
}

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo('2026-10-17 10:00:00');
});

it('stores the content and expires after the configured ttl', function () {
    $report = ReportUrl::store('id,name', 'report.csv');

    Storage::disk('local')->assertExists("report-urls/{$report->uuid}/report.csv");
    expect(Storage::disk('local')->get($report->path))->toBe('id,name')
        ->and($report->file_name)->toBe('report.csv')
        ->and($report->size)->toBe(7)
        ->and($report->expires_at->toDateTimeString())->toBe('2026-10-24 10:00:00')
        ->and($report->owner_type)->toBeNull();

    expect(ReportUrl::store('x', 'a.csv', null, 30)->expires_at->toDateTimeString())->toBe('2026-10-17 10:30:00');

    config(['report-url.ttl' => null]);
    expect(ReportUrl::store('x', 'a.csv')->expires_at)->toBeNull();
});

it('streams a local file and never uses the file name as a path', function () {
    $local = tempnam(sys_get_temp_dir(), 'report');
    file_put_contents($local, str_repeat('a', 1000));

    $report = ReportUrl::storeFile($local, '../../etc/passwd.csv', user());
    unlink($local);

    expect($report->path)->toBe("report-urls/{$report->uuid}/passwd.csv")
        ->and($report->size)->toBe(1000)
        ->and(Storage::disk('local')->size($report->path))->toBe(1000)
        ->and($report->owner_type)->toBe((new TestUser)->getMorphClass());
});

it('downloads the file through its signed url', function () {
    Event::fake([ReportUrlDownloaded::class]);
    $report = ReportUrl::store('id,name', 'report.csv');

    $response = $this->get($report->url());

    $response->assertOk()->assertDownload('report.csv');
    expect($response->streamedContent())->toBe('id,name')
        ->and($report->refresh()->downloads)->toBe(1)
        ->and($report->last_downloaded_at->toDateTimeString())->toBe('2026-10-17 10:00:00');
    Event::assertDispatched(ReportUrlDownloaded::class, fn ($event) => $event->reportUrl->is($report));
});

it('refuses a tampered, unsigned or expired link', function () {
    $report = ReportUrl::store('id,name', 'report.csv', null, 60);
    $url = $report->url();

    $this->get($url.'x')->assertForbidden();
    $this->get(route('report-url.download', ['reportUrl' => $report->uuid]))->assertForbidden();

    // Moved earlier after the link was sent: the signature still holds, the record doesn't.
    $report->update(['expires_at' => now()->subMinute()]);
    $this->get($url)->assertStatus(410);

    $this->travel(2)->hours();
    $this->get($url)->assertForbidden();
});

it('only lets the owner download an owned file', function () {
    $owner = user();
    $url = ReportUrl::store('id,name', 'report.csv', $owner)->url();

    $this->get($url)->assertForbidden();

    Route::get('login', fn () => 'login')->name('login');
    Route::getRoutes()->refreshNameLookups();
    $this->get($url)->assertRedirect('login');

    $this->actingAs(user('Bob'))->get($url)->assertForbidden();
    $this->actingAs($owner)->get($url)->assertOk();

    config(['report-url.restrict_to_owner' => false]);
    $this->actingAs(user('Bob'))->get($url)->assertOk();
});

it('prunes expired links with their files', function () {
    $expired = ReportUrl::store('a', 'a.csv', null, 60);
    $valid = ReportUrl::store('b', 'b.csv');
    $forever = tap(ReportUrl::store('c', 'c.csv'))->update(['expires_at' => null]);
    $this->travel(2)->hours();

    $this->artisan('report-url:prune', ['--dry-run' => true])->expectsOutputToContain('Would delete 1 report URL(s).')->assertSuccessful();
    expect(ReportUrlModel::query()->count())->toBe(3);

    $this->artisan('report-url:prune')->expectsOutputToContain('Deleted 1 report URL(s).')->assertSuccessful();

    expect(ReportUrlModel::query()->pluck('id')->all())->toEqualCanonicalizing([$valid->id, $forever->id]);
    Storage::disk('local')->assertMissing($expired->path);
    Storage::disk('local')->assertMissing(dirname($expired->path));
    Storage::disk('local')->assertExists($valid->path);
});

it('schedules the prune daily on one server', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'report-url:prune'));

    expect($event?->expression)->toBe('0 0 * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->onOneServer)->toBeTrue();
});

it('hosts laravel-saved-filter reports as links for the recipient', function () {
    $owner = user();
    $host = app(HostsReportFiles::class);

    $link = $host->host(new ReportFile('id,name', 'report.csv', 1), $owner);

    expect($host)->toBeInstanceOf(SavedFilterReportHost::class)
        ->and($link->expiresAt->toDateTimeString())->toBe('2026-10-24 10:00:00');
    $this->actingAs($owner)->get($link->url)->assertOk()->assertDownload('report.csv');
});

it('keeps the app\'s own HostsReportFiles binding and can be turned off', function () {
    // Registering again, as the app would boot with its own binding or the config off.
    $register = fn () => (new ReportUrlServiceProvider(app()))->register();
    $own = new class implements HostsReportFiles
    {
        public function host(ReportFile $file, Model $user): ?ReportLink
        {
            return null;
        }
    };

    app()->offsetUnset(HostsReportFiles::class);
    app()->instance(HostsReportFiles::class, $own);
    $register();
    expect(app(HostsReportFiles::class))->toBe($own);

    app()->offsetUnset(HostsReportFiles::class);
    config(['report-url.integrations.saved_filter' => false]);
    $register();
    expect(app()->bound(HostsReportFiles::class))->toBeFalse();
});
