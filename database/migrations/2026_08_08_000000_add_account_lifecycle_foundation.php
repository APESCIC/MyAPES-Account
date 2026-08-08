<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $emailCollision = DB::table('users')
            ->selectRaw('LOWER(email) AS normalized_email, COUNT(*) AS aggregate')
            ->groupByRaw('LOWER(email)')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($emailCollision) {
            throw new RuntimeException(
                'Cannot add the account lifecycle while users contain case-insensitive email collisions.',
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 30)->nullable()->unique()->after('oidc_sub');
            $table->timestamp('onboarding_completed_at')->nullable()->after('email_verified_at');
        });

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('town_city')->nullable();
            $table->string('county')->nullable();
            $table->string('postcode', 16)->nullable();
            $table->char('country', 2)->nullable()->default('GB');
            $table->string('mobile_number', 32)->nullable();
            $table->string('landline_number', 32)->nullable();
            $table->string('whatsapp_number', 32)->nullable();
            $table->string('telegram_username', 32)->nullable();
        });

        Schema::create('user_service_selections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sub_core_key', 64);
            $table->timestamps();
            $table->unique(['user_id', 'sub_core_key']);
            $table->index('sub_core_key');
        });

        Schema::create('user_contact_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('calls')->default(false);
            $table->boolean('sms')->default(false);
            $table->boolean('whatsapp')->default(false);
            $table->boolean('telegram')->default(false);
            $table->boolean('email')->default(false);
            $table->string('policy_version', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_consent_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16);
            $table->boolean('granted');
            $table->string('policy_version', 64);
            $table->string('source', 16);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['user_id', 'recorded_at']);
        });

        Schema::create('oidc_link_intents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });

        DB::table('users')
            ->orderBy('id')
            ->select(['id', 'email'])
            ->each(static function (object $user): void {
                DB::table('users')->where('id', $user->id)->update([
                    'email' => mb_strtolower(trim((string) $user->email)),
                ]);
            });

        $now = now();
        $publicUserIds = DB::table('users')
            ->whereIn('identity_type', ['local', 'hybrid'])
            ->orderBy('id')
            ->pluck('id');
        $serviceKeys = ['apes-cic', 'shelter-rescue', 'pet-care-clinic'];

        foreach ($publicUserIds as $userId) {
            DB::table('user_contact_preferences')->insertOrIgnore([
                'user_id' => $userId,
                'calls' => false,
                'sms' => false,
                'whatsapp' => false,
                'telegram' => false,
                'email' => false,
                'policy_version' => null,
                'confirmed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($serviceKeys as $serviceKey) {
                DB::table('user_service_selections')->insertOrIgnore([
                    'user_id' => $userId,
                    'sub_core_key' => $serviceKey,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oidc_link_intents');
        Schema::dropIfExists('contact_consent_events');
        Schema::dropIfExists('user_contact_preferences');
        Schema::dropIfExists('user_service_selections');

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'address_line_1', 'address_line_2', 'town_city', 'county',
                'postcode', 'country', 'mobile_number', 'landline_number',
                'whatsapp_number', 'telegram_username',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'onboarding_completed_at']);
        });
    }
};
