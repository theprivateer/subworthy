<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Post;
use App\Models\ReadLater;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadLaterControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_redirects_to_home_when_user_has_no_read_later_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/readlater')
            ->assertRedirect('/home');
    }

    public function test_index_shows_posts_grouped_by_feed(): void
    {
        $user  = User::factory()->create();
        $feed1 = Feed::factory()->create();
        $feed2 = Feed::factory()->create();
        $post1 = Post::factory()->create(['feed_id' => $feed1->id]);
        $post2 = Post::factory()->create(['feed_id' => $feed2->id]);

        ReadLater::factory()->create(['user_id' => $user->id, 'post_id' => $post1->id]);
        ReadLater::factory()->create(['user_id' => $user->id, 'post_id' => $post2->id]);

        $this->actingAs($user)
            ->get('/readlater')
            ->assertOk()
            ->assertViewIs('readlater.index')
            ->assertViewHas('posts', fn ($posts) => $posts->has($feed1->id) && $posts->has($feed2->id));
    }

    public function test_index_applies_subscription_title_overrides(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create(['title' => 'Original Title']);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'feed_id' => $feed->id,
            'title'   => 'My Override',
        ]);
        $post = Post::factory()->create(['feed_id' => $feed->id]);
        ReadLater::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $response = $this->actingAs($user)->get('/readlater');

        $posts     = $response->viewData('posts');
        $feedItems = $posts->first();
        $this->assertEquals('My Override', $feedItems->first()->post->feed_title);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_removes_the_read_later_entry(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        ReadLater::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $this->actingAs($user)->delete("/readlater/{$post->id}");

        $this->assertDatabaseMissing('read_laters', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_destroy_only_removes_the_authenticated_users_entry(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create();

        ReadLater::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        ReadLater::factory()->create(['user_id' => $other->id, 'post_id' => $post->id]);

        $this->actingAs($user)->delete("/readlater/{$post->id}");

        $this->assertDatabaseMissing('read_laters', ['user_id' => $user->id, 'post_id' => $post->id]);
        $this->assertDatabaseHas('read_laters', ['user_id' => $other->id, 'post_id' => $post->id]);
    }
}
