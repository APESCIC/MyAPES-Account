<?php

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
        Schema::create('pet_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('service_domain', ['shelter', 'petcare']);
            $table->string('name');
            $table->string('species')->nullable();
            $table->unsignedTinyInteger('age_years')->nullable();
            $table->enum('sex', ['male', 'female', 'unknown'])->default('unknown');
            $table->enum('neutering_status', ['neutered', 'not_neutered', 'unknown'])->default('unknown');
            $table->text('health_issues')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['service_domain', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_profiles');
    }
};
