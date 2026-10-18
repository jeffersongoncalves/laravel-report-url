<?php

namespace JeffersonGoncalves\ReportUrl\Integrations;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ReportUrl\Facades\ReportUrl;
use JeffersonGoncalves\SavedFilter\Contracts\HostsReportFiles;
use JeffersonGoncalves\SavedFilter\ReportFile;
use JeffersonGoncalves\SavedFilter\ReportLink;

/**
 * Lets laravel-saved-filter email reports too large to attach as a download link for the recipient.
 */
class SavedFilterReportHost implements HostsReportFiles
{
    public function host(ReportFile $file, Model $user): ?ReportLink
    {
        $report = ReportUrl::store($file->content, $file->fileName, $user);

        return new ReportLink($report->url(), $report->expires_at);
    }
}
