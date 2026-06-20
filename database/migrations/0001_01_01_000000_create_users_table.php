<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('avatar')->nullable();
            $table->string('account_type', 20)->nullable(); // private, company
            $table->string('locale', 5)->default('bg');
            $table->string('theme', 10)->default('light');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // google, facebook, apple
            $table->string('provider_id');
            $table->string('avatar')->nullable();
            $table->text('token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};