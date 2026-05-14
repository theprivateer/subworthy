<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'edition'        => 1,
            'issue_date'     => fake()->dateTimeBetween('-1 month', 'now'),
            'posts'          => json_encode([]),
            'posts_excluded' => json_encode([]),
        ];
    }
}
