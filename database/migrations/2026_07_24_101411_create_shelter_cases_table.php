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
        Schema::create('shelter_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('case_type', ['adoption', 'surrender', 'rescue', 'fostering']);
            $table->enum('status', ['open', 'in_review', 'closed'])->default('open');
            $table->string('title');
            $table->text('details')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['case_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelter_cases');
    }
};
