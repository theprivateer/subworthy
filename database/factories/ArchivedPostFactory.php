<?php

namespace Database\Factories;

use App\Models\ArchivedPost;
use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArchivedPost>
 */
class ArchivedPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'feed_id'   => Feed::factory(),
            'source_id' => fake()->unique()->uuid(),
        ];
    }
}
