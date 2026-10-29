<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_records', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('canonical')->nullable()->index();
            $table->string('robots')->default('index, follow');
            $table->string('image')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('social_title')->nullable();
            $table->text('social_description')->nullable();
            $table->string('social_image')->nullable();
            $table->json('keyphrases')->nullable();
            $table->json('keywords')->nullable();
            $table->string('locale', 35)->nullable();
            $table->boolean('cornerstone')->default(false);
            $table->boolean('include_in_sitemap')->default(true);
            $table->decimal('sitemap_priority', 2, 1)->nullable();
            $table->string('sitemap_changefreq', 20)->nullable();
            $table->unsignedInteger('inbound_links')->default(0);
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });

        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source', 2048)->unique();
            $table->string('destination', 2048)->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_regex')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('business_type')->default('LocalBusiness');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('url')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country', 8)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('logo')->nullable();
            $table->string('image')->nullable();
            $table->string('price_range')->nullable();
            $table->string('vat_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_videos', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('seoable');
            $table->string('page_url');
            $table->string('title');
            $table->text('description');
            $table->string('thumbnail_url');
            $table->string('content_url')->nullable();
            $table->string('player_url')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('family_friendly')->default(true);
            $table->json('tags')->nullable();
            $table->boolean('requires_subscription')->default(false);
            $table->boolean('live')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_videos');
        Schema::dropIfExists('seo_locations');
        Schema::dropIfExists('seo_redirects');
        Schema::dropIfExists('seo_records');
    }
};
