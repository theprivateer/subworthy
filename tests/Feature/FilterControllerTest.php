<?php

namespace Tests\Feature;

use App\Models\Filter;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_filter_attached_to_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post("/filter/{$subscription->id}/create", [
            'field' => 'title',
            'operator' => 'contains',
            'pattern' => 'keyword',
        ]);

        $this->assertDatabaseHas('filters', [
            'subscription_id' => $subscription->id,
            'field' => 'title',
            'operator' => 'contains',
            'pattern' => 'keyword',
        ]);
    }

    public function test_store_rejects_request_missing_field(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/filter/{$subscription->id}/create", [
                'operator' => 'contains',
                'pattern' => 'keyword',
            ])
            ->assertSessionHasErrors('field');
    }

    public function test_store_rejects_request_missing_operator(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/filter/{$subscription->id}/create", [
                'field' => 'title',
                'pattern' => 'keyword',
            ])
            ->assertSessionHasErrors('operator');
    }

    public function test_store_rejects_request_missing_pattern(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/filter/{$subscription->id}/create", [
                'field' => 'title',
                'operator' => 'contains',
            ])
            ->assertSessionHasErrors('pattern');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_changes_filter_field_operator_and_pattern(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        $filter = Filter::factory()->create([
            'subscription_id' => $subscription->id,
            'field' => 'title',
            'operator' => 'contains',
            'pattern' => 'old',
        ]);

        $this->actingAs($user)->post("/filter/{$filter->id}/edit", [
            "field_{$filter->id}" => 'raw',
            "operator_{$filter->id}" => 'equals',
            "pattern_{$filter->id}" => 'new-value',
        ]);

        $this->assertDatabaseHas('filters', [
            'id' => $filter->id,
            'field' => 'raw',
            'operator' => 'equals',
            'pattern' => 'new-value',
        ]);
    }

    public function test_update_rejects_request_missing_required_fields(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        $filter = Filter::factory()->create(['subscription_id' => $subscription->id]);

        $this->actingAs($user)
            ->post("/filter/{$filter->id}/edit", [])
            ->assertSessionHasErrors();
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_the_filter(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        $filter = Filter::factory()->create(['subscription_id' => $subscription->id]);

        $this->actingAs($user)->delete("/filter/{$filter->id}");

        $this->assertDatabaseMissing('filters', ['id' => $filter->id]);
    }

    // -------------------------------------------------------------------------
    // authorization
    // -------------------------------------------------------------------------

    public function test_store_rejects_filter_on_another_users_subscription(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $victim->id]);

        $this->actingAs($attacker)->post("/filter/{$subscription->id}/create", [
            'field' => 'title',
            'operator' => 'contains',
            'pattern' => 'keyword',
        ])->assertNotFound();

        $this->assertDatabaseCount('filters', 0);
    }

    public function test_update_rejects_filter_belonging_to_another_user(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $victim->id]);
        $filter = Filter::factory()->create([
            'subscription_id' => $subscription->id,
            'pattern' => 'original',
        ]);

        $this->actingAs($attacker)->post("/filter/{$filter->id}/edit", [
            'field_'.$filter->id => 'title',
            'operator_'.$filter->id => 'contains',
            'pattern_'.$filter->id => 'hijacked',
        ])->assertNotFound();

        $this->assertDatabaseHas('filters', [
            'id' => $filter->id,
            'pattern' => 'original',
        ]);
    }

    public function test_destroy_rejects_filter_belonging_to_another_user(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $victim->id]);
        $filter = Filter::factory()->create(['subscription_id' => $subscription->id]);

        $this->actingAs($attacker)->delete("/filter/{$filter->id}")->assertNotFound();

        $this->assertDatabaseHas('filters', ['id' => $filter->id]);
    }
}
