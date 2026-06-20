<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->boolean('alert_enabled')->default(true)->after('filters');
            $table->timestamp('last_notified_at')->nullable()->after('alert_enabled');
            $table->unsignedInteger('last_match_count')->default(0)->after('last_notified_at');
            $table->index('user_id');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('inquiries_count')->default(0)->after('views_count');
            $table->unsignedInteger('phone_clicks_count')->default(0)->after('inquiries_count');
            $table->index(['status', 'brand_id', 'model_id', 'year']);
            $table->index(['status', 'region_id']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
        });

        Schema::table('favorite_listings', function (Blueprint $table) {
            $table->index('listing_id');
        });

        Schema::create('listing_price_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('old_price');
            $table->unsignedInteger('new_price');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['listing_id', 'created_at']);
        });

        Schema::create('listing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['listing_id', 'status']);
        });

        Schema::create('listing_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['listing_id', 'created_at']);
            $table->index(['listing_id', 'user_id']);
        });

        Schema::create('recently_viewed_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 64)->nullable();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_id', 'viewed_at']);
            $table->unique(['user_id', 'listing_id']);
            $table->unique(['session_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_viewed_listings');
        Schema::dropIfExists('listing_inquiries');
        Schema::dropIfExists('listing_reports');
        Schema::dropIfExists('listing_price_changes');

        Schema::table('favorite_listings', function (Blueprint $table) {
            $table->dropIndex(['listing_id']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'verified_at']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['status', 'brand_id', 'model_id', 'year']);
            $table->dropIndex(['status', 'region_id']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude', 'inquiries_count', 'phone_clicks_count']);
        });

        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn(['alert_enabled', 'last_notified_at', 'last_match_count']);
        });
    }
};