<?php

namespace Database\Factories;

use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feed_id' => Feed::factory(),
            'title'   => null,
        ];
    }
}
