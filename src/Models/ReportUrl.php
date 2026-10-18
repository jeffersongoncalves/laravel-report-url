<?php

namespace JeffersonGoncalves\ReportUrl\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $owner_type
 * @property string|null $owner_id
 * @property string $disk
 * @property string $path
 * @property string $file_name
 * @property string|null $mime_type
 * @property int $size
 * @property CarbonInterface|null $expires_at
 * @property int $downloads
 * @property CarbonInterface|null $last_downloaded_at
 */
class ReportUrl extends Model
{
    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'size',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'downloads' => 'integer',
            'expires_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('report-url.table', 'report_urls');
    }

    protected static function booted(): void
    {
        // The file lives in its own {directory}/{uuid} folder, so the folder goes with it.
        static::deleting(fn (ReportUrl $reportUrl) => Storage::disk($reportUrl->disk)->deleteDirectory(dirname($reportUrl->path)));
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    /**
     * @param  Builder<ReportUrl>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lessThanOrEqualTo(now());
    }

    public function isOwnedBy(?Authenticatable $user): bool
    {
        return $user instanceof Model
            && $this->owner_type === $user->getMorphClass()
            && $this->owner_id === (string) $user->getKey();
    }

    /**
     * A signed link: it can't be tampered with or guessed, and it stops working on its own.
     */
    public function url(): string
    {
        return URL::temporarySignedRoute('report-url.download', $this->expires_at ?? now()->addYears(10), ['reportUrl' => $this->uuid]);
    }
}
