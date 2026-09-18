<?php

namespace App\Models;

use Database\Factories\SiteProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

#[Fillable([
    'full_name',
    'professional_title',
    'hero_intro',
    'about_text',
    'location',
    'public_email',
    'public_phone',
    'availability_text',
    'profile_image_path',
    'cv_path',
    'years_experience',
    'hero_code_lines',
    'about_question',
    'about_answer',
])]
class SiteProfile extends Model
{
    /** @use HasFactory<SiteProfileFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (): void {
            if (static::query()->exists()) {
                throw new LogicException('Only one site profile row may exist.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'hero_code_lines' => 'array',
            'years_experience' => 'integer',
        ];
    }
}
