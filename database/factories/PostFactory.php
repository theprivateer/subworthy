<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'feed_id'      => Feed::factory(),
            'source_id'    => fake()->unique()->uuid(),
            'url'          => fake()->url(),
            'title'        => fake()->sentence(),
            'preview'      => fake()->paragraph(),
            'raw'          => '<p>' . fake()->paragraphs(3, true) . '</p>',
            'fetched_raw'  => null,
            'audio_url'    => null,
            'published_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
            'modified_at'  => null,
        ];
    }
}
