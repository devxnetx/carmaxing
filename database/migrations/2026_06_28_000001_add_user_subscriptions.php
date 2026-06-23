<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('subscribe_price_digest')->default(false)->after('theme');
            $table->boolean('subscribe_new_listings_digest')->default(false)->after('subscribe_price_digest');
            $table->boolean('subscribe_news')->default(false)->after('subscribe_new_listings_digest');
            $table->timestamp('subscription_prompted_at')->nullable()->after('subscribe_news');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscribe_price_digest',
                'subscribe_new_listings_digest',
                'subscribe_news',
                'subscription_prompted_at',
            ]);
        });
    }
};