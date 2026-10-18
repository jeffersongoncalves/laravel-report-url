<?php

namespace JeffersonGoncalves\ReportUrl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JeffersonGoncalves\ReportUrl\Models\ReportUrl;

class ReportUrlManager
{
    /**
     * Keep the content as a file and return its record: `->url()` gives the download link.
     */
    public function store(string $content, string $fileName, ?Model $owner = null, ?int $ttlMinutes = null): ReportUrl
    {
        return $this->keep($fileName, $owner, $ttlMinutes, strlen($content), fn (string $disk, string $directory, string $name) => Storage::disk($disk)->put("{$directory}/{$name}", $content));
    }

    /**
     * Same as store(), streaming a local file instead of loading it into memory.
     */
    public function storeFile(string $localPath, string $fileName, ?Model $owner = null, ?int $ttlMinutes = null): ReportUrl
    {
        return $this->keep($fileName, $owner, $ttlMinutes, (int) filesize($localPath), fn (string $disk, string $directory, string $name) => Storage::disk($disk)->putFileAs($directory, new File($localPath), $name));
    }

    /**
     * @param  callable(string, string, string): mixed  $write
     */
    protected function keep(string $fileName, ?Model $owner, ?int $ttlMinutes, int $size, callable $write): ReportUrl
    {
        $uuid = (string) Str::uuid();
        $disk = config('report-url.disk', 'local');
        $directory = trim(config('report-url.directory', 'report-urls'), '/')."/{$uuid}";
        // The name is shown to the downloader, never trusted as a path.
        $name = basename(str_replace('\\', '/', $fileName)) ?: 'report';
        $ttl = $ttlMinutes ?? config('report-url.ttl');

        $write($disk, $directory, $name);

        $reportUrl = new ReportUrl([
            'uuid' => $uuid,
            'disk' => $disk,
            'path' => "{$directory}/{$name}",
            'file_name' => $name,
            'mime_type' => rescue(fn () => Storage::disk($disk)->mimeType("{$directory}/{$name}") ?: null, null, false),
            'size' => $size,
            'expires_at' => $ttl === null ? null : now()->addMinutes((int) $ttl),
        ]);

        if ($owner) {
            $reportUrl->owner()->associate($owner);
        }

        $reportUrl->save();

        return $reportUrl;
    }
}
