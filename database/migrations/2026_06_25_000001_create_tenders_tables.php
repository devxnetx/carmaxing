<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 20)->unique();
            $table->string('slug')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->foreignId('brand_id')->constrained('vehicle_brands');
            $table->foreignId('model_id')->constrained('vehicle_models');
            $table->string('car_variant')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('mileage')->nullable();
            $table->string('fuel_type', 30)->nullable();
            $table->unsignedSmallInteger('engine_power_hp')->nullable();
            $table->string('transmission', 30)->nullable();
            $table->string('body_type', 30)->nullable();
            $table->string('color_exterior', 50)->nullable();
            $table->string('condition', 20)->default('used');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('city')->nullable();
            $table->unsignedInteger('starting_price');
            $table->unsignedInteger('minimum_price')->nullable();
            $table->unsignedInteger('bid_increment')->default(100);
            $table->unsignedTinyInteger('duration_days');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('current_high_bid_amount')->nullable();
            $table->unsignedInteger('bid_count')->default(0);
            $table->foreignId('winning_bid_id')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('tender_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('path_medium')->nullable();
            $table->string('path_thumb')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('tender_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('active');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'status', 'amount']);
            $table->index(['user_id', 'tender_id']);
        });

        Schema::table('tenders', function (Blueprint $table) {
            $table->foreign('winning_bid_id')->references('id')->on('tender_bids')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['winning_bid_id']);
        });

        Schema::dropIfExists('tender_bids');
        Schema::dropIfExists('tender_images');
        Schema::dropIfExists('tenders');
        Schema::dropIfExists('platform_settings');
    }
};