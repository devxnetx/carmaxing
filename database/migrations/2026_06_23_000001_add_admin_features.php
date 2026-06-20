<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('onboarding_completed_at');
        });

        Schema::table('listing_reports', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('admin_notes')->nullable()->after('reviewed_at');
        });

        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_api_key_id')->constrained()->cascadeOnDelete();
            $table->string('method', 10);
            $table->string('path', 255);
            $table->unsignedSmallInteger('status_code');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_api_key_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');

        Schema::table('listing_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'admin_notes']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};