<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name_bg');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_popular')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('vehicle_brands')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('vehicle_models')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 20)->default('model'); // series, model
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
        });

        Schema::create('vehicle_feature_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_bg');
            $table->string('name_en');
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('vehicle_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('vehicle_feature_categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name_bg');
            $table->string('name_en');
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_features');
        Schema::dropIfExists('vehicle_feature_categories');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_brands');
        Schema::dropIfExists('regions');
    }
};