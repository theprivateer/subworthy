<?php

namespace Tests\Feature;

use App\Filters\PostFilterService;
use App\Models\Feed;
use App\Models\Filter;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PostFilterServiceTest extends TestCase
{
    private function filter(Post $post, array $filters): bool
    {
        return PostFilterService::filter($post, new Collection($filters));
    }

    private function makeFilter(string $field, string $operator, string $pattern): Filter
    {
        return new Filter(['field' => $field, 'operator' => $operator, 'pattern' => $pattern]);
    }

    // -------------------------------------------------------------------------
    // Empty / no filters
    // -------------------------------------------------------------------------

    public function test_empty_filter_collection_returns_false(): void
    {
        $post = Post::factory()->make(['title' => 'some title']);

        $this->assertFalse($this->filter($post, []));
    }

    // -------------------------------------------------------------------------
    // contains
    // -------------------------------------------------------------------------

    public function test_contains_matches_case_insensitively(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'contains', 'hello');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_contains_returns_false_when_pattern_is_absent(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'contains', 'missing');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // does not contain
    // -------------------------------------------------------------------------

    public function test_does_not_contain_excludes_when_pattern_is_absent(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'does not contain', 'missing');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_does_not_contain_passes_when_pattern_is_present(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'does not contain', 'hello');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // equals
    // -------------------------------------------------------------------------

    public function test_equals_matches_case_insensitively(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'equals', 'HELLO WORLD');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_equals_returns_false_on_partial_match(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'equals', 'hello');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // does not equal
    // -------------------------------------------------------------------------

    public function test_does_not_equal_excludes_when_values_differ(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'does not equal', 'something else');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_does_not_equal_passes_when_values_match(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'does not equal', 'hello world');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // regex()
    // -------------------------------------------------------------------------

    public function test_regex_excludes_when_pattern_matches(): void
    {
        $post = Post::factory()->make(['title' => 'New feature in 2024']);
        $filter = $this->makeFilter('title', 'regex()', '/\d{4}/');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_regex_passes_when_pattern_does_not_match(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'regex()', '/\d{4}/');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // regex no match
    // -------------------------------------------------------------------------

    public function test_regex_no_match_excludes_when_pattern_does_not_apply(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'regex no match', '/\d{4}/');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_regex_no_match_passes_when_pattern_applies(): void
    {
        $post = Post::factory()->make(['title' => 'New feature in 2024']);
        $filter = $this->makeFilter('title', 'regex no match', '/\d{4}/');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // Unknown operator
    // -------------------------------------------------------------------------

    public function test_unknown_operator_returns_false_without_error(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);
        $filter = $this->makeFilter('title', 'invented operator', 'hello');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    // -------------------------------------------------------------------------
    // OR logic — multiple filters
    // -------------------------------------------------------------------------

    public function test_any_single_matching_filter_excludes_post(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);

        $filters = [
            $this->makeFilter('title', 'contains', 'no match'),
            $this->makeFilter('title', 'contains', 'hello'),
        ];

        $this->assertTrue($this->filter($post, $filters));
    }

    public function test_post_is_included_when_all_filters_fail(): void
    {
        $post = Post::factory()->make(['title' => 'Hello World']);

        $filters = [
            $this->makeFilter('title', 'contains', 'no match'),
            $this->makeFilter('title', 'equals', 'wrong value'),
        ];

        $this->assertFalse($this->filter($post, $filters));
    }

    // -------------------------------------------------------------------------
    // Field selection
    // -------------------------------------------------------------------------

    public function test_filter_reads_from_title_field(): void
    {
        $post = Post::factory()->make(['title' => 'target keyword', 'raw' => 'unrelated content']);
        $filter = $this->makeFilter('title', 'contains', 'target');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_filter_on_title_does_not_match_raw_content(): void
    {
        $post = Post::factory()->make(['title' => 'unrelated', 'raw' => 'target keyword']);
        $filter = $this->makeFilter('title', 'contains', 'target');

        $this->assertFalse($this->filter($post, [$filter]));
    }

    public function test_filter_reads_from_raw_field(): void
    {
        $post = Post::factory()->make(['title' => 'unrelated', 'raw' => 'target keyword']);
        $filter = $this->makeFilter('raw', 'contains', 'target');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_filter_reads_from_preview_field(): void
    {
        // preview goes through getPreviewAttribute() which calls the formatter,
        // so we need a feed relation available in memory for getFormatter() to work.
        $feed = Feed::factory()->make();
        $post = Post::factory()->make(['preview' => 'exclusive scoop', 'raw' => 'different raw body']);
        $post->setRelation('feed', $feed);

        $filter = $this->makeFilter('preview', 'contains', 'exclusive');

        $this->assertTrue($this->filter($post, [$filter]));
    }

    public function test_filter_on_preview_does_not_match_title(): void
    {
        $feed = Feed::factory()->make();
        $post = Post::factory()->make(['title' => 'exclusive scoop', 'preview' => 'ordinary news', 'raw' => 'different raw body']);
        $post->setRelation('feed', $feed);

        $filter = $this->makeFilter('preview', 'contains', 'exclusive');

        $this->assertFalse($this->filter($post, [$filter]));
    }
}
