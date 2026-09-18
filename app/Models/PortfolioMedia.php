<?php

namespace App\Models;

use App\Enums\PortfolioMediaFit;
use App\Enums\PortfolioMediaType;
use Database\Factories\PortfolioMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['portfolio_id', 'path', 'media_type', 'fit', 'alt_text', 'is_primary', 'sort_order'])]
class PortfolioMedia extends Model
{
    /** @use HasFactory<PortfolioMediaFactory> */
    use HasFactory;

    protected $table = 'portfolio_media';

    protected $attributes = [
        'fit' => 'cover',
        'is_primary' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'media_type' => PortfolioMediaType::class,
            'fit' => PortfolioMediaFit::class,
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PortfolioMedia $media): void {
            if (! $media->portfolio_id) {
                return;
            }

            $media->path = $media->normalizePath($media->path);

            if (! $media->exists && ! $media->isDirty('fit')) {
                $media->fit = $media->defaultFit()->value;
            }

            if (blank($media->getAttribute('fit'))) {
                $media->fit = $media->defaultFit()->value;
            }

            if (! $media->exists && ! $media->is_primary && ! static::query()->where('portfolio_id', $media->portfolio_id)->exists()) {
                $media->is_primary = true;
            }

            if ($media->exists && ! $media->is_primary && ! static::query()
                ->where('portfolio_id', $media->portfolio_id)
                ->whereKeyNot($media->getKey())
                ->where('is_primary', true)
                ->exists()) {
                $media->is_primary = true;
            }
        });

        static::saved(function (PortfolioMedia $media): void {
            if (! $media->is_primary) {
                return;
            }

            DB::transaction(function () use ($media): void {
                static::query()
                    ->where('portfolio_id', $media->portfolio_id)
                    ->whereKeyNot($media->getKey())
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            });
        });

        static::updated(function (PortfolioMedia $media): void {
            $oldPath = $media->getOriginal('path');

            if ($oldPath !== $media->path) {
                $media->deleteUnreferencedFile($oldPath);
            }
        });

        static::deleted(function (PortfolioMedia $media): void {
            $media->deleteUnreferencedFile($media->path);

            if (static::query()->where('portfolio_id', $media->portfolio_id)->where('is_primary', true)->exists()) {
                return;
            }

            static::query()
                ->where('portfolio_id', $media->portfolio_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(1)
                ->update(['is_primary' => true]);
        });
    }

    /**
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    private function defaultFit(): PortfolioMediaFit
    {
        return $this->mediaType() === PortfolioMediaType::Logo
            ? PortfolioMediaFit::Contain
            : PortfolioMediaFit::Cover;
    }

    private function mediaType(): ?PortfolioMediaType
    {
        $mediaType = $this->getAttribute('media_type');

        if ($mediaType instanceof PortfolioMediaType) {
            return $mediaType;
        }

        return PortfolioMediaType::tryFrom((string) $mediaType);
    }

    private function normalizePath(?string $path): string
    {
        $path = Str::of((string) $path)->replace('\\', '/')->ltrim('/')->toString();

        if (blank($path) || ! Str::startsWith($path, 'portfolio/') || Str::contains($path, ['..', '//'])) {
            throw new \InvalidArgumentException('Invalid portfolio media path.');
        }

        return $path;
    }

    private function deleteUnreferencedFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $path = $this->normalizePath($path);

        if (! static::query()->where('path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
