<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('sub_core_key', 64);
            $table->string('module_key', 64);
            $table->json('settings');
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sub_core_key', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
