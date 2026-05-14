<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    public function test_root_redirects_to_home(): void
    {
        $this->get('/')->assertRedirect(route('home'));
    }

    public function test_cancelled_page_is_accessible_without_authentication(): void
    {
        $this->get('/cancelled')->assertOk()->assertViewIs('user.user.cancelled');
    }
}
