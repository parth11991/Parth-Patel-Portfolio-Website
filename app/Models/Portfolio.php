<?php

namespace App\Models;

use App\Enums\PortfolioStatus;
use Database\Factories\PortfolioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'experience_id',
    'title',
    'slug',
    'short_description',
    'full_description',
    'project_url',
    'repository_url',
    'started_at',
    'completed_at',
    'is_ongoing',
    'technologies',
    'role',
    'outcome',
    'sort_order',
    'status',
    'published_at',
])]
class Portfolio extends Model
{
    /** @use HasFactory<PortfolioFactory> */
    use HasFactory;

    protected $attributes = [
        'is_ongoing' => false,
        'sort_order' => 0,
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'completed_at' => 'date',
            'is_ongoing' => 'boolean',
            'technologies' => 'array',
            'sort_order' => 'integer',
            'status' => PortfolioStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Portfolio $portfolio): void {
            $portfolio->media()->get()->each->delete();
        });
    }

    /**
     * @return BelongsTo<Experience, $this>
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    /**
     * @return HasMany<PortfolioMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(PortfolioMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasOne<PortfolioMedia, $this>
     */
    public function primaryMedia(): HasOne
    {
        return $this->hasOne(PortfolioMedia::class)->where('is_primary', true);
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @return Builder<Portfolio>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PortfolioStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @return Builder<Portfolio>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('published_at');
    }
}
