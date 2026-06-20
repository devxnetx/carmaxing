<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            User::query()->update(['is_admin' => false]);
        }
    }

    public function down(): void
    {
        // Cannot reliably restore previous per-user admin flags.
    }
};