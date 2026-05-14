<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Issue;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueControllerTest extends TestCase
{
    use RefreshDatabase;

    private function issueWithPost(): array
    {
        $owner        = User::factory()->create();
        $feed         = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $owner->id, 'feed_id' => $feed->id]);
        $post         = Post::factory()->create(['feed_id' => $feed->id]);
        $issue        = Issue::factory()->create([
            'user_id' => $owner->id,
            'posts'   => json_encode([$post->id]),
        ]);

        return [$owner, $issue, $post];
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_is_publicly_accessible_without_authentication(): void
    {
        [, $issue] = $this->issueWithPost();

        $this->get("/issue/{$issue->id}")->assertOk();
    }

    public function test_show_returns_issue_view(): void
    {
        [, $issue] = $this->issueWithPost();

        $this->get("/issue/{$issue->id}")->assertViewIs('issue.show');
    }

    public function test_show_logs_interaction_on_issue_owner(): void
    {
        [$owner, $issue] = $this->issueWithPost();

        $this->assertNull($owner->last_interaction_at);

        $this->get("/issue/{$issue->id}");

        $this->assertNotNull($owner->fresh()->last_interaction_at);
    }

    public function test_show_sets_auth_user_to_false_for_guests(): void
    {
        [, $issue] = $this->issueWithPost();

        $this->get("/issue/{$issue->id}")
            ->assertViewHas('authUser', false);
    }

    public function test_show_sets_auth_user_to_the_authenticated_user(): void
    {
        [$owner, $issue] = $this->issueWithPost();

        $this->actingAs($owner)
            ->get("/issue/{$issue->id}")
            ->assertViewHas('authUser', fn ($u) => $u->id === $owner->id);
    }

    public function test_show_sets_auth_user_to_viewer_not_owner_when_viewing_another_users_issue(): void
    {
        [$owner, $issue] = $this->issueWithPost();
        $viewer          = User::factory()->create();

        $this->actingAs($viewer)
            ->get("/issue/{$issue->id}")
            ->assertViewHas('authUser', fn ($u) => $u->id === $viewer->id);
    }
}
