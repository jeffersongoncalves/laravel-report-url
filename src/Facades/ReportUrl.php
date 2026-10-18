<?php

namespace JeffersonGoncalves\ReportUrl\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\ReportUrl\ReportUrlManager;

/**
 * @method static \JeffersonGoncalves\ReportUrl\Models\ReportUrl store(string $content, string $fileName, ?\Illuminate\Database\Eloquent\Model $owner = null, ?int $ttlMinutes = null)
 * @method static \JeffersonGoncalves\ReportUrl\Models\ReportUrl storeFile(string $localPath, string $fileName, ?\Illuminate\Database\Eloquent\Model $owner = null, ?int $ttlMinutes = null)
 *
 * @see ReportUrlManager
 */
class ReportUrl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ReportUrlManager::class;
    }
}
