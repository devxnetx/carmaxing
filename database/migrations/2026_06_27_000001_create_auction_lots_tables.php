<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_cars_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending');
            $table->json('filters')->nullable();
            $table->unsignedSmallInteger('pages_per_brand')->default(1);
            $table->unsignedSmallInteger('per_page')->nullable();
            $table->unsignedInteger('total_fetched')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auction_lots', function (Blueprint $table) {
            $table->id();
            $table->string('external_lot', 32)->unique();
            $table->string('auction_source', 16);
            $table->string('vin', 17)->nullable()->index();
            $table->string('tag')->nullable();
            $table->string('title');
            $table->foreignId('brand_id')->nullable()->constrained('vehicle_brands')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->string('car_variant')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->string('location')->nullable();
            $table->string('loss_type')->nullable();
            $table->string('primary_damage')->nullable();
            $table->string('start_code')->nullable();
            $table->string('start_code_color', 16)->nullable();
            $table->string('seller')->nullable();
            $table->string('seller_long')->nullable();
            $table->boolean('seller_trusted')->default(false);
            $table->string('sale_document')->nullable();
            $table->string('sale_document_external')->nullable();
            $table->string('sale_document_state')->nullable();
            $table->string('search_status', 32)->nullable()->index();
            $table->unsignedTinyInteger('status_code')->nullable();
            $table->unsignedInteger('prebid_price_usd')->nullable();
            $table->unsignedInteger('final_bid_usd')->nullable();
            $table->unsignedInteger('buy_now_price_usd')->nullable();
            $table->unsignedInteger('estimated_min_usd')->nullable();
            $table->unsignedInteger('estimated_max_usd')->nullable();
            $table->unsignedInteger('time_left_seconds')->nullable();
            $table->string('prebid_close_time')->nullable();
            $table->boolean('has_video')->default(false);
            $table->boolean('has_360_view')->default(false);
            $table->string('video_url')->nullable();
            $table->string('view_360_url')->nullable();
            $table->boolean('sold_before')->default(false);
            $table->json('specs')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('source_url');
            $table->foreignId('bid_cars_import_run_id')->nullable()->constrained('bid_cars_import_runs')->nullOnDelete();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'search_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_lots');
        Schema::dropIfExists('bid_cars_import_runs');
    }
};