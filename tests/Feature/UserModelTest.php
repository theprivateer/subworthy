<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\ReadLater;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // delivery_time UTC conversion (boot saving hook)
    // -------------------------------------------------------------------------

    public function test_utc_delivery_time_is_stored_unchanged(): void
    {
        $user = User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0800',
        ]);

        $this->assertEquals('0800', $user->delivery_time);
    }

    public function test_local_time_is_converted_to_utc_on_save(): void
    {
        // Africa/Johannesburg is UTC+2 with no DST — always a predictable offset.
        $user = User::factory()->create([
            'timezone'            => 'Africa/Johannesburg',
            'delivery_time_local' => '0800',
        ]);

        $this->assertEquals('0600', $user->delivery_time);
    }

    public function test_local_time_crossing_midnight_is_stored_as_previous_day_utc(): void
    {
        // Asia/Tokyo is UTC+9 with no DST.
        // 02:00 JST = 17:00 UTC the previous day — format('Hi') still returns '1700'.
        $user = User::factory()->create([
            'timezone'            => 'Asia/Tokyo',
            'delivery_time_local' => '0200',
        ]);

        $this->assertEquals('1700', $user->delivery_time);
    }

    public function test_delivery_time_is_recomputed_when_timezone_changes(): void
    {
        $user = User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0800',
        ]);

        $user->update(['timezone' => 'Africa/Johannesburg']);

        $this->assertEquals('0600', $user->delivery_time);
    }

    public function test_delivery_time_is_recomputed_when_local_time_changes(): void
    {
        $user = User::factory()->create([
            'timezone'            => 'Africa/Johannesburg',
            'delivery_time_local' => '0800',
        ]);

        $user->update(['delivery_time_local' => '1000']);

        $this->assertEquals('0800', $user->delivery_time);
    }

    // -------------------------------------------------------------------------
    // Default values applied by boot saving hook
    // -------------------------------------------------------------------------

    public function test_null_timezone_defaults_to_utc_on_save(): void
    {
        $user = User::factory()->make([
            'timezone'            => null,
            'delivery_time_local' => '0800',
        ]);
        $user->save();

        $this->assertEquals('UTC', $user->timezone);
    }

    public function test_null_delivery_time_local_defaults_to_midnight_on_save(): void
    {
        $user = User::factory()->make([
            'timezone'            => 'UTC',
            'delivery_time_local' => null,
        ]);
        $user->save();

        $this->assertEquals('0000', $user->delivery_time_local);
        $this->assertEquals('0000', $user->delivery_time);
    }

    public function test_null_timezone_and_null_delivery_time_both_default_on_save(): void
    {
        $user = User::factory()->make([
            'timezone'            => null,
            'delivery_time_local' => null,
        ]);
        $user->save();

        $this->assertEquals('UTC', $user->timezone);
        $this->assertEquals('0000', $user->delivery_time_local);
        $this->assertEquals('0000', $user->delivery_time);
    }

    // -------------------------------------------------------------------------
    // hasDefaultDeliverySettings()
    // -------------------------------------------------------------------------

    public function test_has_default_delivery_settings_returns_true_for_utc_midnight(): void
    {
        $user = User::factory()->make([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0000',
        ]);

        $this->assertTrue($user->hasDefaultDeliverySettings());
    }

    public function test_has_default_delivery_settings_returns_false_for_non_utc_timezone(): void
    {
        $user = User::factory()->make([
            'timezone'            => 'Africa/Johannesburg',
            'delivery_time_local' => '0000',
        ]);

        $this->assertFalse($user->hasDefaultDeliverySettings());
    }

    public function test_has_default_delivery_settings_returns_false_for_non_midnight_time(): void
    {
        $user = User::factory()->make([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0800',
        ]);

        $this->assertFalse($user->hasDefaultDeliverySettings());
    }

    public function test_has_default_delivery_settings_returns_false_when_both_differ(): void
    {
        $user = User::factory()->make([
            'timezone'            => 'Asia/Tokyo',
            'delivery_time_local' => '0800',
        ]);

        $this->assertFalse($user->hasDefaultDeliverySettings());
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function test_subscriptions_relation_returns_only_the_users_own_subscriptions(): void
    {
        $user         = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        Subscription::factory()->create(); // belongs to a different user

        $this->assertTrue($user->subscriptions->contains($subscription));
        $this->assertCount(1, $user->subscriptions);
    }

    public function test_issues_relation_returns_only_the_users_own_issues(): void
    {
        $user  = User::factory()->create();
        $issue = Issue::factory()->create(['user_id' => $user->id]);
        Issue::factory()->create(); // belongs to a different user

        $this->assertTrue($user->issues->contains($issue));
        $this->assertCount(1, $user->issues);
    }

    public function test_read_laters_relation_returns_only_the_users_own_items(): void
    {
        $user      = User::factory()->create();
        $readLater = ReadLater::factory()->create(['user_id' => $user->id]);
        ReadLater::factory()->create(); // belongs to a different user

        $this->assertTrue($user->readLaters->contains($readLater));
        $this->assertCount(1, $user->readLaters);
    }

    // -------------------------------------------------------------------------
    // logInteraction()
    // -------------------------------------------------------------------------

    public function test_log_interaction_updates_last_interaction_at_timestamp(): void
    {
        $user   = User::factory()->create(['last_interaction_at' => null]);
        $before = now();

        $user->logInteraction();

        $user->refresh();
        $this->assertNotNull($user->last_interaction_at);
        $this->assertTrue($user->last_interaction_at->isAfter($before->subSecond()));
    }

    public function test_log_interaction_overwrites_a_previous_timestamp(): void
    {
        $past = now()->subDay();
        $user = User::factory()->create(['last_interaction_at' => $past]);

        $user->logInteraction();

        $user->refresh();
        $this->assertTrue($user->last_interaction_at->isAfter($past));
    }
}
