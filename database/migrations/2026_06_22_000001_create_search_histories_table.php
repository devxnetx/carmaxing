<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->json('filters');
            $table->string('filters_hash', 64);
            $table->timestamp('searched_at');
            $table->timestamps();

            $table->unique(['user_id', 'filters_hash']);
            $table->index(['user_id', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};