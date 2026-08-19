<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STAFF_ROLES = [
        'staff',
        'administrator',
        'super-admin',
    ];

    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('job_title')->nullable();
            $table->string('team', 32)->nullable();
            $table->string('work_phone', 32)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        DB::table('users')
            ->where('identity_type', 'hybrid')
            ->update(['identity_type' => 'cloudron_oidc']);

        $staffUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('roles.guard_name', 'web')
            ->whereIn('roles.name', self::STAFF_ROLES)
            ->distinct()
            ->pluck('model_has_roles.model_id');

        $now = now();
        foreach ($staffUserIds as $userId) {
            DB::table('staff_profiles')->insertOrIgnore([
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
