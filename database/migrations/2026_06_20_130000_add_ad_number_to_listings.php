<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedInteger('ad_number')->nullable()->after('external_id');
            $table->unique(['company_id', 'ad_number']);
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'ad_number']);
            $table->dropColumn('ad_number');
        });
    }
};