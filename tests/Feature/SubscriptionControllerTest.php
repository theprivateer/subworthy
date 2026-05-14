<?php

namespace Tests\Feature;

use App\Jobs\RemoveUnsubscribedArticlesFromIssues;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_returns_subscription_edit_view(): void
    {
        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get("/subscription/{$subscription->id}/edit")
            ->assertOk()
            ->assertViewIs('subscription.edit')
            ->assertViewHas('subscription', fn ($s) => $s->id === $subscription->id);
    }

    public function test_edit_with_another_users_subscription_returns_404(): void
    {
        $user  = User::factory()->create();
        $other = Subscription::factory()->create(); // belongs to a different user

        $this->actingAs($user)
            ->get("/subscription/{$other->id}/edit")
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_changes_the_subscription_title(): void
    {
        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        $this->actingAs($user)
            ->post("/subscription/{$subscription->id}/edit", ['title' => 'New Title']);

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'title' => 'New Title']);
    }

    public function test_update_accepts_null_title_to_clear_override(): void
    {
        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'title' => 'Override']);

        $this->actingAs($user)
            ->post("/subscription/{$subscription->id}/edit", ['title' => null]);

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'title' => null]);
    }

    public function test_update_rejects_title_over_255_characters(): void
    {
        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/subscription/{$subscription->id}/edit", ['title' => str_repeat('a', 256)])
            ->assertSessionHasErrors('title');
    }

    public function test_update_with_another_users_subscription_returns_404(): void
    {
        $user  = User::factory()->create();
        $other = Subscription::factory()->create();

        $this->actingAs($user)
            ->post("/subscription/{$other->id}/edit", ['title' => 'Hack'])
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_the_subscription(): void
    {
        Queue::fake([RemoveUnsubscribedArticlesFromIssues::class]);

        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/subscription/{$subscription->id}");

        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    public function test_destroy_dispatches_remove_unsubscribed_articles_job(): void
    {
        Queue::fake([RemoveUnsubscribedArticlesFromIssues::class]);

        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/subscription/{$subscription->id}");

        Queue::assertPushed(RemoveUnsubscribedArticlesFromIssues::class);
    }

    public function test_destroy_redirects_to_home(): void
    {
        Queue::fake([RemoveUnsubscribedArticlesFromIssues::class]);

        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete("/subscription/{$subscription->id}")
            ->assertRedirect('/home');
    }

    public function test_destroy_with_another_users_subscription_returns_404(): void
    {
        Queue::fake([RemoveUnsubscribedArticlesFromIssues::class]);

        $user  = User::factory()->create();
        $other = Subscription::factory()->create();

        $this->actingAs($user)
            ->delete("/subscription/{$other->id}")
            ->assertNotFound();
    }
}
