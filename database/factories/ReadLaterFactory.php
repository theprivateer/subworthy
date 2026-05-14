<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\ReadLater;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadLater>
 */
class ReadLaterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
