<?php

namespace Tests\Feature;

use App\Jobs\CheckFeed;
use App\Jobs\CreateDailyIssue;
use App\Models\Feed;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    // Monday 2024-01-15 09:30:00 UTC — ISO day 1, time string '0930'.
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-01-15 09:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CheckFeed dispatch
    // -------------------------------------------------------------------------

    public function test_check_feed_is_dispatched_for_feed_with_matching_next_check_at(): void
    {
        Queue::fake();

        Feed::factory()->create(['next_check_at' => '0930']);

        $this->artisan('schedule:run');

        Queue::assertPushed(CheckFeed::class);
    }

    public function test_check_feed_is_not_dispatched_for_feed_whose_time_does_not_match(): void
    {
        Queue::fake();

        Feed::factory()->create(['next_check_at' => '1000']);

        $this->artisan('schedule:run');

        Queue::assertNotPushed(CheckFeed::class);
    }

    public function test_check_feed_is_not_dispatched_for_feed_with_null_next_check_at(): void
    {
        Queue::fake();

        Feed::factory()->create(['next_check_at' => null]);

        $this->artisan('schedule:run');

        Queue::assertNotPushed(CheckFeed::class);
    }

    public function test_check_feed_is_only_dispatched_for_feeds_with_matching_time(): void
    {
        Queue::fake();

        $matchingFeed    = Feed::factory()->create(['next_check_at' => '0930']);
        $nonMatchingFeed = Feed::factory()->create(['next_check_at' => '1000']);

        $this->artisan('schedule:run');

        Queue::assertPushed(CheckFeed::class, 1);
        Queue::assertPushed(CheckFeed::class, fn (CheckFeed $job) => $this->jobFeedId($job) === $matchingFeed->id);
        Queue::assertNotPushed(CheckFeed::class, fn (CheckFeed $job) => $this->jobFeedId($job) === $nonMatchingFeed->id);
    }

    // -------------------------------------------------------------------------
    // CreateDailyIssue dispatch
    // -------------------------------------------------------------------------

    public function test_create_daily_issue_is_dispatched_for_user_matching_delivery_time_and_day(): void
    {
        Queue::fake();

        // UTC timezone, '0930' local → delivery_time '0930'. Days include Monday (1).
        User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0930',
            'days_of_week'        => '1234567',
            'paused'              => null,
        ]);

        $this->artisan('schedule:run');

        Queue::assertPushed(CreateDailyIssue::class);
    }

    public function test_create_daily_issue_is_not_dispatched_for_user_with_wrong_delivery_time(): void
    {
        Queue::fake();

        User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '1000',
            'days_of_week'        => '1234567',
            'paused'              => null,
        ]);

        $this->artisan('schedule:run');

        Queue::assertNotPushed(CreateDailyIssue::class);
    }

    public function test_create_daily_issue_is_not_dispatched_when_today_is_not_in_days_of_week(): void
    {
        Queue::fake();

        // '234567' excludes Monday (1).
        User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0930',
            'days_of_week'        => '234567',
            'paused'              => null,
        ]);

        $this->artisan('schedule:run');

        Queue::assertNotPushed(CreateDailyIssue::class);
    }

    public function test_create_daily_issue_is_not_dispatched_for_paused_user(): void
    {
        Queue::fake();

        User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0930',
            'days_of_week'        => '1234567',
            'paused'              => now(),
        ]);

        $this->artisan('schedule:run');

        Queue::assertNotPushed(CreateDailyIssue::class);
    }

    public function test_create_daily_issue_is_dispatched_only_for_eligible_users(): void
    {
        Queue::fake();

        User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '0930',
            'days_of_week'        => '1234567',
            'paused'              => null,
        ]);

        // Wrong time — should be skipped.
        User::factory()->create([
            'timezone'            => 'UTC',
            'delivery_time_local' => '1000',
            'days_of_week'        => '1234567',
            'paused'              => null,
        ]);

        $this->artisan('schedule:run');

        Queue::assertPushed(CreateDailyIssue::class, 1);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function jobFeedId(CheckFeed $job): int
    {
        // The feed property is protected — access via reflection.
        $ref = new \ReflectionProperty($job, 'feed');
        $ref->setAccessible(true);
        return $ref->getValue($job)->id;
    }
}
