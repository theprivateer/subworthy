<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_hashes_and_saves_the_new_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/password', [
            'password'              => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_update_rejects_passwords_shorter_than_8_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/user/password', [
                'password'              => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_update_rejects_mismatched_password_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/user/password', [
                'password'              => 'newpassword',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');
    }
}
