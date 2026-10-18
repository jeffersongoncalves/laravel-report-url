<?php

namespace JeffersonGoncalves\ReportUrl\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\ReportUrl\Models\ReportUrl;

class PruneCommand extends Command
{
    protected $signature = 'report-url:prune
        {--dry-run : Only count what would be deleted}';

    protected $description = 'Delete expired report URLs and their files';

    public function handle(): int
    {
        $expired = ReportUrl::query()->expired();
        $count = $expired->count();

        if (! $this->option('dry-run')) {
            // Through the model, so the files go too.
            $expired->lazyById()->each->delete();
        }

        $this->components->info(($this->option('dry-run') ? 'Would delete' : 'Deleted')." {$count} report URL(s).");

        return self::SUCCESS;
    }
}
