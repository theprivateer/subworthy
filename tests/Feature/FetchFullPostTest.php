<?php

namespace Tests\Feature;

use App\Fetchers\FetcherContract;
use App\Jobs\FetchFullPost;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FetchFullPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetcher_fetch_is_called_with_the_post(): void
    {
        $post    = Post::factory()->create();
        $fetcher = $this->createMock(FetcherContract::class);
        $fetcher->expects($this->once())->method('fetch')->with($this->equalTo($post));

        (new FetchFullPost($post, $fetcher))->handle();
    }

    public function test_fetcher_result_is_returned(): void
    {
        $post    = Post::factory()->create();
        $fetcher = $this->createMock(FetcherContract::class);
        $fetcher->method('fetch')->willReturn('fetched-content');

        $result = (new FetchFullPost($post, $fetcher))->handle();

        $this->assertEquals('fetched-content', $result);
    }
}
