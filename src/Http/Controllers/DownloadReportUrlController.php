<?php

namespace JeffersonGoncalves\ReportUrl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ReportUrl\Events\ReportUrlDownloaded;
use JeffersonGoncalves\ReportUrl\Models\ReportUrl;
use Symfony\Component\HttpFoundation\Response;

class DownloadReportUrlController
{
    public function __invoke(Request $request, string $reportUrl): Response
    {
        $report = ReportUrl::query()->where('uuid', $reportUrl)->firstOrFail();

        // The signature already expires; this covers an expires_at moved earlier after the link was sent.
        abort_if($report->isExpired(), 410);

        $user = $request->user();

        if (config('report-url.restrict_to_owner', true) && $report->owner_type !== null) {
            if ($user === null) {
                abort_unless(Route::has('login'), 403);

                return redirect()->guest(route('login'));
            }

            abort_unless($report->isOwnedBy($user), 403);
        }

        abort_unless(Storage::disk($report->disk)->exists($report->path), 404);

        $report->forceFill(['downloads' => $report->downloads + 1, 'last_downloaded_at' => now()])->save();

        ReportUrlDownloaded::dispatch($report, $user);

        return Storage::disk($report->disk)->download($report->path, $report->file_name);
    }
}
