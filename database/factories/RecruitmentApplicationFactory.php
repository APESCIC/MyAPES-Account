<?php

namespace Database\Factories;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentApplication>
 */
class RecruitmentApplicationFactory extends Factory
{
    protected $model = RecruitmentApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recruitment_role_id' => RecruitmentRole::factory()->open(),
            'user_id' => User::factory(),
            'status' => RecruitmentApplication::STATUS_SUBMITTED,
            'statement' => fake()->optional()->paragraph(),
            'staff_notes' => null,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'decided_at' => null,
            'withdrawn_at' => null,
        ];
    }

    public function underReview(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentApplication::STATUS_UNDER_REVIEW,
            'reviewed_at' => now(),
        ]);
    }

    public function shortlisted(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentApplication::STATUS_SHORTLISTED,
            'reviewed_at' => now()->subDay(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentApplication::STATUS_REJECTED,
            'reviewed_at' => now()->subDay(),
            'decided_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentApplication::STATUS_ACCEPTED,
            'reviewed_at' => now()->subDay(),
            'decided_at' => now(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentApplication::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentApplication::STATUS_CLOSED,
            'decided_at' => now(),
        ]);
    }

    public function forRole(RecruitmentRole $role): static
    {
        return $this->state(fn (): array => [
            'recruitment_role_id' => $role->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
