<?php

namespace App\Formatters;

use App\Models\Feed;
use DOMDocument;
use HTMLPurifier;
use HTMLPurifier_Config;

class DefaultFormatter implements FormatterContract
{
    protected $purifier;
    /**
     * @var \App\Models\Feed
     */
    protected $feed;

    public function __construct(Feed $feed)
    {
        $this->initPurifier();
        $this->feed = $feed;
    }

    private function initPurifier(): void
    {
        // Setting HTMLPurifier's options
        // TODO: switch to an associative array
        $options = [
            // Allow only paragraph tags
            // and anchor tags wit the href attribute
            [
                'HTML.Allowed',
                'p,br,a[href],img[src|alt],blockquote,em,strong,ul,ol,li,pre,code,h1,h2,h3'
            ],
            // Format end output with Tidy
            [
                'Output.TidyFormat',
                true
            ],
            // Assume XHTML 1.0 Strict Doctype
            [
                'HTML.Doctype',
                'XHTML 1.0 Strict'
            ],
            // Disable cache, but see note after the example
            [
                'Cache.DefinitionImpl',
                null
            ]
        ];

        // Configuring HTMLPurifier
        $config = HTMLPurifier_Config::createDefault();

        foreach ($options as $option) {
            $config->set($option[0], $option[1]);
        }

        // Creating a HTMLPurifier with it's config
        $this->purifier = new HTMLPurifier($config);
    }

    public function render($raw): string
    {
        $raw = $this->purifier->purify($raw);

        $raw = $this->addBlankTargetToAnchors($raw);
        return $this->substituteImageUrls($raw);
    }

    protected function addBlankTargetToAnchors($raw): string
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $raw);

        foreach ($dom->getElementsByTagName('a') as $link) {
            $link->setAttribute('target', '_blank');
        }

        return $dom->saveHTML($dom->documentElement);
    }

    protected function substituteImageUrls($raw): string
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $raw);

        $tags = $dom->getElementsByTagName('img');

        foreach ($tags as $tag) {
            $src = $tag->getAttribute('src');

            // if
            if($src[0] == '/' && $src[1] != '/')
            {
                $src = $this->feed->tld . $src;
                $tag->setAttribute('src', $src);
            }
        }

        return $dom->saveHTML($dom->documentElement);
    }
}
