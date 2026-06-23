<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->index('slug');
            $table->index(['status', 'company_id', 'published_at']);
        });

        Schema::table('listing_feature', function (Blueprint $table) {
            $table->index(['vehicle_feature_id', 'listing_id']);
        });

        Schema::table('listing_images', function (Blueprint $table) {
            $table->index(['listing_id', 'sort_order']);
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->index(['brand_id', 'parent_id', 'sort_order']);
        });

        Schema::table('mobile_bg_import_runs', function (Blueprint $table) {
            $table->index(['company_id', 'status']);
        });

        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->index(['company_id', 'is_active']);
        });

        Schema::table('tender_images', function (Blueprint $table) {
            $table->index(['tender_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('tender_images', function (Blueprint $table) {
            $table->dropIndex(['tender_id', 'sort_order']);
        });

        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'is_active']);
        });

        Schema::table('mobile_bg_import_runs', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropIndex(['brand_id', 'parent_id', 'sort_order']);
        });

        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropIndex(['listing_id', 'sort_order']);
        });

        Schema::table('listing_feature', function (Blueprint $table) {
            $table->dropIndex(['vehicle_feature_id', 'listing_id']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['status', 'company_id', 'published_at']);
            $table->dropIndex(['slug']);
        });
    }
};