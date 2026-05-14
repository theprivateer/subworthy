<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_logs_interaction_on_the_user(): void
    {
        $user = User::factory()->create(['last_interaction_at' => null]);
        $post = Post::factory()->create();

        $this->get("/link/{$user->id}/{$post->id}");

        $this->assertNotNull($user->fresh()->last_interaction_at);
    }

    public function test_show_redirects_to_the_post_url(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['url' => 'https://example.com/article']);

        $this->get("/link/{$user->id}/{$post->id}")
            ->assertRedirect('https://example.com/article');
    }
}
