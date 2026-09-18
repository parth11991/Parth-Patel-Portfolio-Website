<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('professional_title');
            $table->text('hero_intro');
            $table->text('about_text');
            $table->string('location');
            $table->string('public_email');
            $table->string('public_phone')->nullable();
            $table->string('availability_text')->nullable();
            $table->string('profile_image_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->json('hero_code_lines')->nullable();
            $table->string('about_question')->nullable();
            $table->text('about_answer')->nullable();
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('default_theme')->default('system')->index();
            $table->string('accent_color');
            $table->string('meta_title');
            $table->text('meta_description');
            $table->string('canonical_url')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('contact_recipient_email')->nullable();
            $table->timestamps();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url');
            $table->string('icon_key');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->index();
            $table->string('icon_key')->nullable();
            $table->unsignedTinyInteger('proficiency')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('role_title');
            $table->string('employment_type')->nullable();
            $table->string('location')->nullable();
            $table->text('summary');
            $table->date('started_at')->index();
            $table->date('ended_at')->nullable()->index();
            $table->boolean('is_current')->default(false)->index();
            $table->string('company_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('education', function (Blueprint $table) {
            $table->id();
            $table->string('institution');
            $table->string('qualification');
            $table->string('field_of_study')->nullable();
            $table->date('started_at')->nullable()->index();
            $table->date('ended_at')->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description');
            $table->longText('full_description')->nullable();
            $table->string('project_url')->nullable();
            $table->string('repository_url')->nullable();
            $table->date('started_at')->nullable()->index();
            $table->date('completed_at')->nullable()->index();
            $table->boolean('is_ongoing')->default(false)->index();
            $table->json('technologies');
            $table->string('role')->nullable();
            $table->text('outcome')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('portfolio_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('media_type')->index();
            $table->string('fit')->default('cover')->index();
            $table->string('alt_text');
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->index(['portfolio_id', 'sort_order']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('subject');
            $table->text('message');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('portfolio_media');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('education');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('site_profiles');
    }
};
