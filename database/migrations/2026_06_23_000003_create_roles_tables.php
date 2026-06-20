<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        $now = now();

        Role::query()->insert([
            ['name' => 'Administrator', 'slug' => Role::ADMIN, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Member', 'slug' => Role::MEMBER, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $adminRole = Role::query()->where('slug', Role::ADMIN)->first();

        if ($adminRole && Schema::hasColumn('users', 'is_admin')) {
            User::query()->update(['is_admin' => false]);
            User::query()
                ->where('email', 'admin@carmaxing.local')
                ->update(['is_admin' => true]);

            User::query()
                ->where('is_admin', true)
                ->each(function (User $user) use ($adminRole) {
                    $user->roles()->syncWithoutDetaching([$adminRole->id]);
                });
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('onboarding_completed_at');
        });

        $adminRole = Role::query()->where('slug', Role::ADMIN)->first();

        if ($adminRole) {
            User::query()
                ->whereHas('roles', fn ($q) => $q->where('roles.id', $adminRole->id))
                ->update(['is_admin' => true]);
        }

        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};