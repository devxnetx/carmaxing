<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_bg_import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('source_url');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('total_found')->default(0);
            $table->unsignedSmallInteger('created_count')->default(0);
            $table->unsignedSmallInteger('updated_count')->default(0);
            $table->unsignedSmallInteger('skipped_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_bg_import_runs');
    }
};