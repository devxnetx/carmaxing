<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('mobile_bg_url')->nullable()->after('website');
            $table->timestamp('mobile_bg_last_sync_at')->nullable()->after('mobile_bg_url');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['mobile_bg_url', 'mobile_bg_last_sync_at']);
        });
    }
};