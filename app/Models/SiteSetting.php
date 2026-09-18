<?php

namespace App\Models;

use App\Enums\SiteTheme;
use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable([
    'site_name',
    'default_theme',
    'accent_color',
    'meta_title',
    'meta_description',
    'canonical_url',
    'og_image_path',
    'contact_recipient_email',
])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (): void {
            if (static::query()->exists()) {
                throw new LogicException('Only one site settings row may exist.');
            }
        });
    }

    protected $attributes = [
        'default_theme' => 'system',
    ];

    protected function casts(): array
    {
        return [
            'default_theme' => SiteTheme::class,
        ];
    }
}
