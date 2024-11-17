<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid("uuid")->nullable(true)->unique();
        });

        // generate UUID for existing users table
        User::query()
            ->whereNull('uuid')
            ->get()
            ->each(function (User $user) {
                info(str()->uuid());
                $user->uuid = str()->uuid();
                $user->save();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique("users_uuid_unique");
            $table->dropColumn('uuid');
        });
    }
};