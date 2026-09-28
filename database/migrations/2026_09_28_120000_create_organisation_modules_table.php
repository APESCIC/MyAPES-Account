<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_modules', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamp('enabled_at')->nullable();
            $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_modules');
    }
};
