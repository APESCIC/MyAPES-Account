<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_sync_runs', function (Blueprint $table): void {
            $table->unsignedInteger('users_seen')->nullable()->after('groups_missing');
            $table->unsignedInteger('users_created')->nullable()->after('users_seen');
            $table->unsignedInteger('users_updated')->nullable()->after('users_created');
        });
    }

    public function down(): void
    {
        Schema::table('directory_sync_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'users_seen',
                'users_created',
                'users_updated',
            ]);
        });
    }
};
