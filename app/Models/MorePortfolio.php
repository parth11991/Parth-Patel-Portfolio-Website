<?php

namespace App\Models;

use Database\Factories\MorePortfolioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'project_url', 'technologies', 'sort_order', 'is_visible'])]
class MorePortfolio extends Model
{
    /** @use HasFactory<MorePortfolioFactory> */
    use HasFactory;

    protected $attributes = [
        'is_visible' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<MorePortfolio>  $query
     * @return Builder<MorePortfolio>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * @param  Builder<MorePortfolio>  $query
     * @return Builder<MorePortfolio>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
