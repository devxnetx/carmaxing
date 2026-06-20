<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tender_rules_accepted_at')->nullable()->after('onboarding_completed_at');
            $table->string('tender_rules_version', 10)->nullable()->after('tender_rules_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tender_rules_accepted_at', 'tender_rules_version']);
        });
    }
};