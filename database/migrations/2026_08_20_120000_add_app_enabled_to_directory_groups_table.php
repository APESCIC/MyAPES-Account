<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_groups', function (Blueprint $table): void {
            $table->boolean('app_enabled')
                ->default(true)
                ->after('status');
        });

        $required = config('myapes.directory.required_groups', []);
        if ($required !== []) {
            DB::table('directory_groups')
                ->whereIn('name', $required)
                ->update(['app_enabled' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('directory_groups', function (Blueprint $table): void {
            $table->dropColumn('app_enabled');
        });
    }
};
