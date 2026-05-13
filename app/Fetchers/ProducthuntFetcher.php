<?php

namespace App\Fetchers;

use App\Models\Post;
use Illuminate\Support\Str;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class ProducthuntFetcher extends AbstractFetcher implements FetcherContract
{
    public function fetch(Post $post)
    {
        $client = new HttpBrowser(HttpClient::create());

        $crawler = $client->request('GET', $post->url);

        $crawler->filter('#__NEXT_DATA__')->each(function ($node) use ($post) {
            $data = $this->extractData($node->text());

            $data['fetched_raw'] = '<blockquote>' . $data['fetched_raw'] . '</blockquote>' . $post->raw;
            $post->update($data);
        });

        return true;
    }

    private function extractData($json)
    {
        $data = json_decode($json, true);

        // Product Hunt embeds a Next.js Apollo cache in __NEXT_DATA__. The first entry
        // in apolloState is always the current product's data object.
        $post = array_shift($data['props']['apolloState']);

        return [
            'title' => $post['name'],
            'preview' => $post['tagline'],
            'fetched_raw' => Str::markdown($post['description']),
        ];
    }
}
