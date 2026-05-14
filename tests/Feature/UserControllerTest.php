<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // show (public profile)
    // -------------------------------------------------------------------------

    public function test_show_displays_public_profile_for_valid_username(): void
    {
        $user = User::factory()->create(['username' => 'johndoe']);

        $this->get('/@johndoe')
            ->assertOk()
            ->assertViewIs('user.user.show');
    }

    public function test_show_returns_404_for_unknown_username(): void
    {
        $this->get('/@nobody')->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    public function test_edit_returns_account_edit_view(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/user')
            ->assertOk()
            ->assertViewIs('user.user.edit')
            ->assertSee('Upload OPML')
            ->assertViewHas('user', fn ($u) => $u->id === $user->id)
            ->assertViewHas('timezone')
            ->assertViewHas('times');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_changes_the_users_email_address(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->post('/user', [
            'email'    => 'new@example.com',
            'username' => $user->username,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_update_changes_the_users_username(): void
    {
        $user = User::factory()->create(['username' => 'oldname']);

        $this->actingAs($user)->post('/user', [
            'email'    => $user->email,
            'username' => 'newname',
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'newname']);
    }

    public function test_update_rejects_duplicate_email_address(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $user     = User::factory()->create();

        $this->actingAs($user)
            ->post('/user', ['email' => 'taken@example.com', 'username' => $user->username])
            ->assertSessionHasErrors('email');
    }

    public function test_update_rejects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create(['username' => 'mine']);

        $this->actingAs($user)
            ->post('/user', ['email' => $user->email, 'username' => 'taken'])
            ->assertSessionHasErrors('username');
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_the_account_and_redirects_to_cancelled(): void
    {
        $user = User::factory()->create();

        // Simulate a recent password confirmation so the middleware passes.
        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/user/cancel')
            ->assertRedirect('/cancelled');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_logs_out_the_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/user/cancel');

        $this->assertGuest();
    }

    public function test_destroy_requires_password_confirmation(): void
    {
        $user = User::factory()->create();

        // Without a recent password confirmation the middleware should redirect.
        $this->actingAs($user)
            ->get('/user/cancel')
            ->assertRedirect('/confirm-password');
    }
}
