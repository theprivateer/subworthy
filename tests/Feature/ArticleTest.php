<?php

namespace Tests\Feature;

use App\Livewire\Article;
use App\Models\Feed;
use App\Models\Post;
use App\Models\ReadLater;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSetup(): array
    {
        $owner = User::factory()->create();
        $feed  = Feed::factory()->create();
        Subscription::factory()->create(['user_id' => $owner->id, 'feed_id' => $feed->id]);
        $post  = Post::factory()->create(['feed_id' => $feed->id]);

        return [$owner, $post];
    }

    // -------------------------------------------------------------------------
    // mount()
    // -------------------------------------------------------------------------

    public function test_mount_shows_read_later_button_when_viewing_own_issue(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::actingAs($owner)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $owner])
            ->assertSet('showReadLaterButton', true);
    }

    public function test_mount_does_not_show_read_later_button_for_guests(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => false])
            ->assertSet('showReadLaterButton', false);
    }

    public function test_mount_does_not_show_read_later_button_when_viewing_another_users_issue(): void
    {
        [$owner, $post] = $this->makeSetup();
        $visitor        = User::factory()->create();

        Livewire::actingAs($visitor)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $visitor])
            ->assertSet('showReadLaterButton', false);
    }

    public function test_mount_marks_reading_later_true_when_post_is_already_saved(): void
    {
        [$owner, $post] = $this->makeSetup();
        ReadLater::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);

        Livewire::actingAs($owner)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $owner])
            ->assertSet('readingLater', true);
    }

    // -------------------------------------------------------------------------
    // showFull() / showPreview()
    // -------------------------------------------------------------------------

    public function test_show_full_sets_full_article_and_dispatches_post_opened(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => false])
            ->call('showFull')
            ->assertSet('fullArticle', true)
            ->assertDispatched('postOpened');
    }

    public function test_show_preview_sets_full_article_false(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => false])
            ->set('fullArticle', true)
            ->call('showPreview')
            ->assertSet('fullArticle', false);
    }

    // -------------------------------------------------------------------------
    // readLater()
    // -------------------------------------------------------------------------

    public function test_read_later_creates_a_record_for_the_authenticated_user(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::actingAs($owner)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $owner])
            ->call('readLater');

        $this->assertDatabaseHas('read_laters', ['user_id' => $owner->id, 'post_id' => $post->id]);
    }

    public function test_read_later_sets_reading_later_true(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::actingAs($owner)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $owner])
            ->call('readLater')
            ->assertSet('readingLater', true);
    }

    public function test_read_later_returns_403_for_guests(): void
    {
        [$owner, $post] = $this->makeSetup();

        Livewire::test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => false])
            ->call('readLater')
            ->assertStatus(403);
    }

    public function test_read_later_returns_403_when_viewing_another_users_issue(): void
    {
        [$owner, $post] = $this->makeSetup();
        $visitor        = User::factory()->create();

        Livewire::actingAs($visitor)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $visitor])
            ->call('readLater')
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // removeReadLater()
    // -------------------------------------------------------------------------

    public function test_remove_read_later_deletes_the_record(): void
    {
        [$owner, $post] = $this->makeSetup();
        ReadLater::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);

        Livewire::actingAs($owner)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $owner])
            ->call('removeReadLater');

        $this->assertDatabaseMissing('read_laters', ['user_id' => $owner->id, 'post_id' => $post->id]);
    }

    public function test_remove_read_later_sets_reading_later_false(): void
    {
        [$owner, $post] = $this->makeSetup();
        ReadLater::factory()->create(['user_id' => $owner->id, 'post_id' => $post->id]);

        Livewire::actingAs($owner)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $owner])
            ->call('removeReadLater')
            ->assertSet('readingLater', false);
    }

    public function test_remove_read_later_returns_403_for_unauthorised_user(): void
    {
        [$owner, $post] = $this->makeSetup();
        $visitor        = User::factory()->create();

        Livewire::actingAs($visitor)
            ->test(Article::class, ['post' => $post, 'user' => $owner, 'authUser' => $visitor])
            ->call('removeReadLater')
            ->assertStatus(403);
    }
}
