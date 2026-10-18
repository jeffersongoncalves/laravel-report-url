<?php

namespace JeffersonGoncalves\ReportUrl\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use JeffersonGoncalves\ReportUrl\Models\ReportUrl;

class ReportUrlDownloaded
{
    use Dispatchable;

    public function __construct(
        public ReportUrl $reportUrl,
        // Null when the link is open to anyone who has it.
        public ?Authenticatable $user = null,
    ) {}
}
