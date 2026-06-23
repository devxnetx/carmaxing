<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_news_posts', function (Blueprint $table) {
            $table->string('recipient_target', 32)->default('subscribers')->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('site_news_posts', function (Blueprint $table) {
            $table->dropColumn('recipient_target');
        });
    }
};