<?php

namespace Database\Factories;

use App\Models\RecruitmentRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentRole>
 */
class RecruitmentRoleFactory extends Factory
{
    protected $model = RecruitmentRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(RecruitmentRole::CATEGORIES),
            'status' => RecruitmentRole::STATUS_DRAFT,
            'location' => fake()->optional()->city(),
            'commitment' => fake()->optional()->randomElement([
                'Part-time',
                'Full-time',
                'Flexible',
                '2 days per week',
            ]),
            'published_at' => null,
            'closed_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentRole::STATUS_OPEN,
            'published_at' => now(),
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentRole::STATUS_CLOSED,
            'published_at' => now()->subWeek(),
            'closed_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => RecruitmentRole::STATUS_DRAFT,
            'published_at' => null,
            'closed_at' => null,
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(fn (): array => [
            'category' => $category,
        ]);
    }
}
