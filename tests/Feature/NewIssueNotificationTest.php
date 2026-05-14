<?php

namespace Tests\Feature;

use App\Models\Feed;
use App\Models\Issue;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\NewIssue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewIssueNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeIssue(): array
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id, 'feed_id' => $feed->id]);
        $post = Post::factory()->create(['feed_id' => $feed->id]);

        $issue = Issue::factory()->create([
            'user_id' => $user->id,
            'edition' => 5,
            'posts'   => json_encode([$post->id]),
        ]);
        $issue->loadIssue();

        return [$user, $issue, $post, $feed];
    }

    public function test_notification_uses_the_mail_channel(): void
    {
        [$user, $issue] = $this->makeIssue();

        $notification = new NewIssue($issue);

        $this->assertSame(['mail'], $notification->via($user));
    }

    public function test_subject_line_contains_the_edition_number(): void
    {
        [$user, $issue] = $this->makeIssue();

        $mail = (new NewIssue($issue))->toMail($user);

        $this->assertSame('Subworthy, Issue 5', $mail->subject);
    }

    public function test_mail_uses_the_issue_markdown_template(): void
    {
        [$user, $issue] = $this->makeIssue();

        $mail = (new NewIssue($issue))->toMail($user);

        $this->assertSame('mail.issue', $mail->markdown);
    }

    public function test_issue_is_passed_to_the_view(): void
    {
        [$user, $issue] = $this->makeIssue();

        $mail = (new NewIssue($issue))->toMail($user);

        $this->assertSame($issue->id, $mail->viewData['issue']->id);
    }

    public function test_user_is_passed_to_the_view(): void
    {
        [$user, $issue] = $this->makeIssue();

        $mail = (new NewIssue($issue))->toMail($user);

        $this->assertSame($user->id, $mail->viewData['user']->id);
    }

    public function test_issue_posts_are_passed_to_the_view(): void
    {
        [$user, $issue, $post, $feed] = $this->makeIssue();

        $mail = (new NewIssue($issue))->toMail($user);

        $this->assertTrue($mail->viewData['posts']->has($feed->id));
    }
}
