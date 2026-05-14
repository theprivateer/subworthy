<?php

namespace Database\Factories;

use App\Models\Filter;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Filter>
 */
class FilterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'field'           => fake()->randomElement(['title', 'preview', 'raw']),
            'operator'        => fake()->randomElement([
                'contains',
                'does not contain',
                'equals',
                'does not equal',
                'regex()',
                'regex no match',
            ]),
            'pattern'         => fake()->word(),
        ];
    }
}
