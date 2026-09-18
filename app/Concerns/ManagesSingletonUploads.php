<?php

namespace App\Concerns;

use App\Models\SiteProfile;
use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

trait ManagesSingletonUploads
{
    /**
     * @param  array<string>  $allowedMimeTypes
     */
    protected function importSingleSourceAsset(string $sourceDirectory, string $storageDirectory, array $allowedMimeTypes): ?string
    {
        if (! File::isDirectory($sourceDirectory)) {
            return null;
        }

        $validFiles = collect(File::files($sourceDirectory))
            ->filter(fn ($file): bool => in_array(File::mimeType($file->getPathname()), $allowedMimeTypes, true))
            ->values();

        if ($validFiles->count() !== 1) {
            return null;
        }

        $file = $validFiles->first();
        $extension = $this->extensionForMimeType((string) File::mimeType($file->getPathname()));
        $path = "{$storageDirectory}/".Str::ulid().".{$extension}";

        Storage::disk('public')->put($path, File::get($file->getPathname()));

        return $path;
    }

    protected function safeUploadedFileName(TemporaryUploadedFile $file): string
    {
        return (string) Str::of((string) $file->guessExtension())
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->prepend(Str::ulid().'.');
    }

    protected function storeManagedUpload(TemporaryUploadedFile $file, string $directory): string
    {
        $directory = Str::of($directory)->replace('\\', '/')->trim('/')->toString();

        if (blank($directory) || Str::contains($directory, ['..', '//'])) {
            throw new \InvalidArgumentException('Invalid upload directory.');
        }

        $path = "{$directory}/".$this->safeUploadedFileName($file);
        $realPath = $file->getRealPath();

        if (! $realPath) {
            throw new RuntimeException('Uploaded file temporary path is not readable.');
        }

        $stream = fopen($realPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Uploaded file stream could not be opened.');
        }

        try {
            $stored = Storage::disk('public')->put($path, $stream, [
                'visibility' => 'public',
            ]);
        } finally {
            fclose($stream);
        }

        if (! $stored || ! Storage::disk('public')->exists($path)) {
            throw new RuntimeException("Unable to store uploaded file on the public disk at [{$path}]. Check that public/storage exists and is writable by PHP.");
        }

        return $path;
    }

    protected function extensionForMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    protected function normalizeManagedPath(mixed $path, string $directory): ?string
    {
        $path = collect(Arr::wrap($path))->filter(fn (mixed $value): bool => filled($value))->last();

        if (blank($path)) {
            return null;
        }

        $path = Str::of($path)->replace('\\', '/')->ltrim('/')->toString();

        if (! Str::startsWith($path, "{$directory}/")) {
            return null;
        }

        if (Str::contains($path, ['..', '//'])) {
            return null;
        }

        return $path;
    }

    protected function deleteUnreferencedManagedFile(?string $path, string $directory): void
    {
        $path = $this->normalizeManagedPath($path, $directory);

        if ($path === null) {
            return;
        }

        $isReferenced = SiteProfile::query()
            ->where('profile_image_path', $path)
            ->orWhere('cv_path', $path)
            ->exists()
            || SiteSetting::query()
                ->where('og_image_path', $path)
                ->exists();

        if (! $isReferenced) {
            Storage::disk('public')->delete($path);
        }
    }
}
