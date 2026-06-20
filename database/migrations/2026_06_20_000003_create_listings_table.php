<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->constrained('vehicle_brands');
            $table->foreignId('model_id')->constrained('vehicle_models');
            $table->string('title');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published, archived, sold
            $table->unsignedInteger('price');
            $table->string('currency', 3)->default('EUR');
            $table->boolean('price_negotiable')->default(false);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('mileage_unit', 2)->default('km');
            $table->string('fuel_type', 30)->nullable();
            $table->unsignedSmallInteger('engine_power_hp')->nullable();
            $table->unsignedSmallInteger('engine_displacement_cc')->nullable();
            $table->string('transmission', 30)->nullable();
            $table->string('drivetrain', 10)->nullable();
            $table->string('body_type', 30)->nullable();
            $table->string('color_exterior', 50)->nullable();
            $table->string('color_interior', 50)->nullable();
            $table->unsignedTinyInteger('doors')->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->string('euro_standard', 10)->nullable();
            $table->string('registration_type', 30)->nullable();
            $table->string('vin', 17)->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('city')->nullable();
            $table->string('condition', 20)->default('used');
            $table->date('warranty_until')->nullable();
            $table->decimal('wltp_consumption', 5, 1)->nullable();
            $table->decimal('battery_capacity_kwh', 5, 1)->nullable();
            $table->date('first_registration_date')->nullable();
            $table->boolean('has_vin')->default(false);
            $table->boolean('has_video')->default(false);
            $table->boolean('has_vr360')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->string('external_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'id']);
            $table->index(['status', 'published_at']);
            $table->index(['brand_id', 'model_id']);
            $table->index(['price', 'year', 'mileage']);
            $table->unique(['company_id', 'external_id']);
        });

        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('listing_feature', function (Blueprint $table) {
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_feature_id')->constrained('vehicle_features')->cascadeOnDelete();
            $table->primary(['listing_id', 'vehicle_feature_id']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('filters');
            $table->timestamps();
        });

        Schema::create('favorite_listings', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_listings');
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('listing_feature');
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
    }
};