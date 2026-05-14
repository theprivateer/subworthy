<?php

namespace Database\Factories;

use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feed>
 */
class FeedFactory extends Factory
{
    public function definition(): array
    {
        $url = fake()->unique()->url();

        return [
            'url'               => $url,
            'protocol_less_url' => preg_replace('#^https?://#', '', $url),
            'title'             => fake()->company(),
            'link'              => $url,
            'description'       => fake()->sentence(),
            'fetcher'           => null,
            'formatter'         => null,
            'next_check_at'     => null,
        ];
    }
}
