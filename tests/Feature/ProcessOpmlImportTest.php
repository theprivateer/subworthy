<?php

namespace Tests\Feature;

use App\Actions\SubscribeToFeed;
use App\Jobs\ImportOpmlFeed;
use App\Jobs\ProcessOpmlImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessOpmlImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_opml_import_parses_nested_outlines_and_deduplicates_urls(): void
    {
        Queue::fake([ImportOpmlFeed::class]);
        Storage::fake('local');

        $user = User::factory()->create();
        Storage::disk('local')->put('opml-imports/test.opml', <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <opml version="2.0">
              <body>
                <outline text="Folder">
                  <outline text="Nested" xmlUrl="https://example.com/nested.xml" />
                  <outline text="Duplicate" xmlUrl="https://example.com/nested.xml" />
                </outline>
                <outline text="Other" xmlUrl="https://example.com/other.xml/" />
                <outline text="Website Only" htmlUrl="https://example.com" />
              </body>
            </opml>
            XML);

        (new ProcessOpmlImport($user->id, 'opml-imports/test.opml'))->handle(new SubscribeToFeed());

        Queue::assertPushed(ImportOpmlFeed::class, 2);
        Queue::assertPushed(ImportOpmlFeed::class, fn (ImportOpmlFeed $job) => $job->userId === $user->id && $job->url === 'https://example.com/nested.xml');
        Queue::assertPushed(ImportOpmlFeed::class, fn (ImportOpmlFeed $job) => $job->userId === $user->id && $job->url === 'https://example.com/other.xml');
        Storage::disk('local')->assertMissing('opml-imports/test.opml');
    }

    public function test_process_opml_import_deletes_file_and_logs_invalid_xml(): void
    {
        Queue::fake([ImportOpmlFeed::class]);
        Storage::fake('local');
        Log::spy();

        $user = User::factory()->create();
        Storage::disk('local')->put('opml-imports/invalid.opml', '<opml><body>');

        (new ProcessOpmlImport($user->id, 'opml-imports/invalid.opml'))->handle(new SubscribeToFeed());

        Queue::assertNotPushed(ImportOpmlFeed::class);
        Storage::disk('local')->assertMissing('opml-imports/invalid.opml');
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_process_opml_import_deletes_file_and_logs_non_opml_xml(): void
    {
        Queue::fake([ImportOpmlFeed::class]);
        Storage::fake('local');
        Log::spy();

        $user = User::factory()->create();
        Storage::disk('local')->put('opml-imports/rss.xml', <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0"></rss>
            XML);

        (new ProcessOpmlImport($user->id, 'opml-imports/rss.xml'))->handle(new SubscribeToFeed());

        Queue::assertNotPushed(ImportOpmlFeed::class);
        Storage::disk('local')->assertMissing('opml-imports/rss.xml');
        Log::shouldHaveReceived('warning')->once();
    }
}
