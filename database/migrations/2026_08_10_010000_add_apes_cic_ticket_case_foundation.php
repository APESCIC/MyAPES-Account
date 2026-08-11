<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureTicketFoundation();
        $this->ensureCaseFoundation();
        $this->ensureCaseUpdates();
    }

    public function down(): void
    {
        // v0.13.0 is intentionally forward-only after APES CIC Cases is used.
        // Destructive contraction belongs in a separately verified recovery.
    }

    private function ensureTicketFoundation(): void
    {
        if (! Schema::hasColumn('support_tickets', 'sub_core_key')) {
            Schema::table('support_tickets', function (Blueprint $table): void {
                $table->string('sub_core_key', 64)
                    ->nullable()
                    ->after('id');
            });
        }

        DB::table('support_tickets')
            ->whereNull('sub_core_key')
            ->orWhere('sub_core_key', '')
            ->update(['sub_core_key' => 'apes-cic']);

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->string('sub_core_key', 64)
                ->default('apes-cic')
                ->change();
        });

        $this->ensureIndex(
            'support_tickets',
            ['sub_core_key', 'status'],
            'support_tickets_sub_core_status_index',
        );
        $this->ensureIndex(
            'support_tickets',
            ['sub_core_key', 'user_id'],
            'support_tickets_sub_core_owner_index',
        );
    }

    private function ensureCaseFoundation(): void
    {
        if (! Schema::hasColumn('shelter_cases', 'sub_core_key')) {
            Schema::table('shelter_cases', function (Blueprint $table): void {
                $table->string('sub_core_key', 64)
                    ->nullable()
                    ->after('id');
            });
        }
        if (! Schema::hasColumn('shelter_cases', 'category')) {
            Schema::table('shelter_cases', function (Blueprint $table): void {
                $table->string('category')->nullable()->after('case_type');
            });
        }
        if (! Schema::hasColumn('shelter_cases', 'priority')) {
            Schema::table('shelter_cases', function (Blueprint $table): void {
                $table->string('priority', 16)->nullable()->after('category');
            });
        }
        if (! Schema::hasColumn('shelter_cases', 'opened_at')) {
            Schema::table('shelter_cases', function (Blueprint $table): void {
                $table->timestamp('opened_at')->nullable()->after('details');
            });
        }
        if (! Schema::hasColumn('shelter_cases', 'resolved_at')) {
            Schema::table('shelter_cases', function (Blueprint $table): void {
                $table->timestamp('resolved_at')->nullable()->after('opened_at');
            });
        }

        DB::table('shelter_cases')
            ->whereNull('sub_core_key')
            ->orWhere('sub_core_key', '')
            ->update(['sub_core_key' => 'shelter-rescue']);
        DB::table('shelter_cases')
            ->whereNull('priority')
            ->update(['priority' => 'medium']);
        DB::table('shelter_cases')
            ->whereNull('opened_at')
            ->update(['opened_at' => DB::raw('created_at')]);

        Schema::table('shelter_cases', function (Blueprint $table): void {
            $table->string('sub_core_key', 64)
                ->default('shelter-rescue')
                ->change();
            $table->string('priority', 16)
                ->default('medium')
                ->change();
            $table->foreignId('pet_profile_id')->nullable()->change();
            $table->string('case_type')->nullable()->change();
            $table->string('status')->default('open')->change();
        });

        $this->ensureIndex(
            'shelter_cases',
            ['sub_core_key', 'status'],
            'shelter_cases_sub_core_status_index',
        );
        $this->ensureIndex(
            'shelter_cases',
            ['sub_core_key', 'user_id'],
            'shelter_cases_sub_core_owner_index',
        );
        $this->ensureIndex(
            'shelter_cases',
            ['sub_core_key', 'assigned_to', 'status'],
            'shelter_cases_sub_core_assignee_status_index',
        );
    }

    private function ensureCaseUpdates(): void
    {
        if (! Schema::hasTable('case_updates')) {
            Schema::create('case_updates', function (Blueprint $table): void {
                $table->id();
            });
        }

        $requiredColumns = [
            'shelter_case_id',
            'user_id',
            'body',
            'visibility',
            'created_at',
            'updated_at',
        ];
        $missingColumns = array_values(array_filter(
            $requiredColumns,
            static fn (string $column): bool => ! Schema::hasColumn('case_updates', $column),
        ));
        if ($missingColumns !== [] && DB::table('case_updates')->exists()) {
            throw new RuntimeException(
                'The incomplete v0.13 case_updates schema contains data and cannot be repaired automatically.',
            );
        }

        if (! Schema::hasColumn('case_updates', 'shelter_case_id')) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->unsignedBigInteger('shelter_case_id')->after('id');
            });
        }
        if (! Schema::hasColumn('case_updates', 'user_id')) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('shelter_case_id');
            });
        }
        if (! Schema::hasColumn('case_updates', 'body')) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->text('body')->after('user_id');
            });
        }
        if (! Schema::hasColumn('case_updates', 'visibility')) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->enum('visibility', ['public', 'internal'])
                    ->default('public')
                    ->after('body');
            });
        }
        if (! Schema::hasColumn('case_updates', 'created_at')) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->timestamp('created_at')->nullable()->after('visibility');
            });
        }
        if (! Schema::hasColumn('case_updates', 'updated_at')) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
        }

        if (! Schema::hasForeignKey('case_updates', ['shelter_case_id'])) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->foreign('shelter_case_id')
                    ->references('id')
                    ->on('shelter_cases')
                    ->cascadeOnDelete();
            });
        }
        if (! Schema::hasForeignKey('case_updates', ['user_id'])) {
            Schema::table('case_updates', function (Blueprint $table): void {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        $this->ensureIndex(
            'case_updates',
            ['shelter_case_id', 'visibility', 'created_at'],
            'case_updates_case_visibility_created_index',
        );
    }

    /** @param array<int, string> $columns */
    private function ensureIndex(
        string $tableName,
        array $columns,
        string $indexName,
    ): void {
        if (Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }
};
