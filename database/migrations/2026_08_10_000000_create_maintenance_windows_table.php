<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_windows', function (Blueprint $table): void {
            $table->id();
            $table->text('message');
            $table->timestamp('planned_end_at')->nullable();
            $table->string('state', 32);
            $table->string('active_guard', 32)->nullable()->unique();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deactivation_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivation_requested_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->string('failure_summary', 255)->nullable();
            $table->timestamp('failure_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_windows');
    }
};
