<?php

namespace Tests\Feature;

use App\Formatters\DefaultFormatter;
use App\Models\Feed;
use Tests\TestCase;

class DefaultFormatterTest extends TestCase
{
    private function makeFormatter(string $tld = 'https://example.com'): DefaultFormatter
    {
        return new DefaultFormatter(Feed::factory()->make(['tld' => $tld]));
    }

    // -------------------------------------------------------------------------
    // HTML purification
    // -------------------------------------------------------------------------

    public function test_script_tags_and_their_content_are_stripped(): void
    {
        $output = $this->makeFormatter()->render('<p>Hello</p><script>alert("xss")</script>');

        $this->assertStringContainsString('<p>Hello</p>', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('alert', $output);
    }

    public function test_disallowed_attributes_are_stripped_from_allowed_tags(): void
    {
        $output = $this->makeFormatter()->render('<p class="foo" style="color:red" onclick="evil()">Hello</p>');

        $this->assertStringContainsString('<p>Hello</p>', $output);
        $this->assertStringNotContainsString('class=', $output);
        $this->assertStringNotContainsString('style=', $output);
        $this->assertStringNotContainsString('onclick=', $output);
    }

    public function test_disallowed_tag_is_removed_but_text_content_is_preserved(): void
    {
        $output = $this->makeFormatter()->render('<div>Inside a div</div>');

        $this->assertStringContainsString('Inside a div', $output);
        $this->assertStringNotContainsString('<div>', $output);
    }

    public function test_allowed_inline_tags_are_preserved(): void
    {
        $output = $this->makeFormatter()->render('<p><strong>bold</strong> and <em>italic</em></p>');

        $this->assertStringContainsString('<strong>bold</strong>', $output);
        $this->assertStringContainsString('<em>italic</em>', $output);
    }

    public function test_allowed_block_tags_are_preserved(): void
    {
        $output = $this->makeFormatter()->render('<blockquote>quote</blockquote><pre><code>snippet</code></pre>');

        $this->assertStringContainsString('<blockquote>', $output);
        $this->assertStringContainsString('<pre><code>snippet</code></pre>', $output);
    }

    public function test_allowed_list_tags_are_preserved(): void
    {
        $output = $this->makeFormatter()->render('<ul><li>one</li><li>two</li></ul>');

        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>one</li>', $output);
        $this->assertStringContainsString('<li>two</li>', $output);
    }

    public function test_allowed_heading_tags_are_preserved(): void
    {
        $output = $this->makeFormatter()->render('<h1>Title</h1><h2>Sub</h2><h3>Sub-sub</h3>');

        $this->assertStringContainsString('<h1>Title</h1>', $output);
        $this->assertStringContainsString('<h2>Sub</h2>', $output);
        $this->assertStringContainsString('<h3>Sub-sub</h3>', $output);
    }

    // -------------------------------------------------------------------------
    // Anchor target injection
    // -------------------------------------------------------------------------

    public function test_target_blank_is_added_to_anchor_tags(): void
    {
        $output = $this->makeFormatter()->render('<a href="https://example.com">Link</a>');

        $this->assertStringContainsString('target="_blank"', $output);
    }

    public function test_href_is_preserved_when_target_is_added(): void
    {
        $output = $this->makeFormatter()->render('<a href="https://example.com">Link</a>');

        $this->assertStringContainsString('href="https://example.com"', $output);
    }

    public function test_existing_target_attribute_is_overwritten_with_blank(): void
    {
        $output = $this->makeFormatter()->render('<a href="https://example.com" target="_self">Link</a>');

        $this->assertStringContainsString('target="_blank"', $output);
        $this->assertStringNotContainsString('target="_self"', $output);
    }

    public function test_all_anchors_receive_target_blank(): void
    {
        $output = $this->makeFormatter()->render(
            '<a href="https://a.com">one</a> <a href="https://b.com">two</a>'
        );

        $this->assertEquals(2, substr_count($output, 'target="_blank"'));
    }

    // -------------------------------------------------------------------------
    // Image URL substitution
    // -------------------------------------------------------------------------

    public function test_root_relative_image_src_is_resolved_to_feed_domain(): void
    {
        $output = $this->makeFormatter('https://example.com')->render(
            '<img src="/images/photo.jpg" alt="photo">'
        );

        $this->assertStringContainsString('src="https://example.com/images/photo.jpg"', $output);
    }

    public function test_root_relative_image_uses_the_feeds_tld(): void
    {
        $output = $this->makeFormatter('https://other-site.com')->render(
            '<img src="/assets/logo.png" alt="logo">'
        );

        $this->assertStringContainsString('src="https://other-site.com/assets/logo.png"', $output);
    }

    public function test_protocol_relative_image_src_is_not_modified(): void
    {
        $output = $this->makeFormatter()->render('<img src="//cdn.other.com/photo.jpg" alt="photo">');

        $this->assertStringContainsString('src="//cdn.other.com/photo.jpg"', $output);
        $this->assertStringNotContainsString('example.com//cdn', $output);
    }

    public function test_absolute_image_url_is_not_modified(): void
    {
        $output = $this->makeFormatter()->render('<img src="https://other.com/photo.jpg" alt="photo">');

        $this->assertStringContainsString('src="https://other.com/photo.jpg"', $output);
    }

    public function test_absolute_image_from_different_domain_is_not_prefixed(): void
    {
        $output = $this->makeFormatter('https://example.com')->render(
            '<img src="https://cdn.net/img.jpg" alt="img">'
        );

        $this->assertStringNotContainsString('example.com/https', $output);
        $this->assertStringContainsString('src="https://cdn.net/img.jpg"', $output);
    }

    // -------------------------------------------------------------------------
    // Encoding
    // -------------------------------------------------------------------------

    public function test_non_ascii_characters_are_preserved(): void
    {
        $output = $this->makeFormatter()->render('<p>Héllo Wörld</p>');

        $this->assertStringContainsString('Héllo', $output);
        $this->assertStringContainsString('Wörld', $output);
    }
}
