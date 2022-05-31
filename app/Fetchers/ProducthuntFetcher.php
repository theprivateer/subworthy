<?php

namespace App\Fetchers;

use App\Models\Post;
use Goutte\Client;
use Illuminate\Support\Str;

class ProducthuntFetcher extends AbstractFetcher implements FetcherContract
{
    public function fetch(Post $post)
    {
        $client = new Client();

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

        $post = array_shift($data['props']['apolloState']);

        return [
            'title' => $post['name'],
            'preview' => $post['tagline'],
            'fetched_raw' => Str::markdown($post['description']),
        ];
    }
}
