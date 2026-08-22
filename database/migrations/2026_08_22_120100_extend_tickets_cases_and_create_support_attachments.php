<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->string('sub_category')->nullable()->after('service_area');
            $table->string('affected_website_key', 64)->nullable()->after('sub_category');
        });

        Schema::table('shelter_cases', function (Blueprint $table): void {
            $table->string('sub_category')->nullable()->after('category');
            $table->string('affected_website_key', 64)->nullable()->after('sub_category');
        });

        Schema::create('support_attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->enum('kind', ['screenshot', 'screencast']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachments');

        Schema::table('shelter_cases', function (Blueprint $table): void {
            $table->dropColumn(['sub_category', 'affected_website_key']);
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropColumn(['sub_category', 'affected_website_key']);
        });
    }
};
