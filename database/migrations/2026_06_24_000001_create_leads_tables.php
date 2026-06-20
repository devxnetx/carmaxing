<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_extraction_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source_url');
            $table->string('city_slug')->nullable();
            $table->string('city_label')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('total_pages')->default(0);
            $table->unsignedSmallInteger('current_page')->default(0);
            $table->unsignedInteger('total_found')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_extraction_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile_bg_url')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('city')->nullable();
            $table->string('source_city')->nullable();
            $table->json('working_hours')->nullable();
            $table->unsignedSmallInteger('member_since_year')->nullable();
            $table->string('contacted_status', 30)->default('pending_invite');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();

            $table->index('source_city');
            $table->index('contacted_status');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_extraction_runs');
    }
};