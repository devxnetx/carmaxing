<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->string('path_medium')->nullable()->after('path');
            $table->string('path_thumb')->nullable()->after('path_medium');
            $table->unsignedSmallInteger('width')->nullable()->after('path_thumb');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['path_medium', 'path_thumb', 'width', 'height']);
        });
    }
};