<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'timezone'            => 'Europe/London',
            'delivery_time_local' => '0900',
            'days_of_week'        => [1, 2, 3, 4, 5],
        ];
    }

    public function test_update_saves_timezone_delivery_time_and_days_of_week(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/delivery', $this->validPayload());

        $user->refresh();
        $this->assertEquals('Europe/London', $user->timezone);
        $this->assertEquals('0900', $user->delivery_time_local);
        $this->assertEquals('12345', $user->days_of_week);
    }

    public function test_update_rejects_invalid_timezone_string(): void
    {
        $user    = User::factory()->create();
        $payload = array_merge($this->validPayload(), ['timezone' => 'Not/ATimezone']);

        $this->actingAs($user)
            ->post('/user/delivery', $payload)
            ->assertSessionHasErrors('timezone');
    }

    public function test_update_rejects_delivery_time_that_is_not_4_digits(): void
    {
        $user    = User::factory()->create();
        $payload = array_merge($this->validPayload(), ['delivery_time_local' => '930']);

        $this->actingAs($user)
            ->post('/user/delivery', $payload)
            ->assertSessionHasErrors('delivery_time_local');
    }

    public function test_update_rejects_day_values_outside_1_to_7(): void
    {
        $user    = User::factory()->create();
        $payload = array_merge($this->validPayload(), ['days_of_week' => [0, 8]]);

        $this->actingAs($user)
            ->post('/user/delivery', $payload)
            ->assertSessionHasErrors();
    }

    public function test_update_accepts_empty_days_of_week_and_stores_empty_string(): void
    {
        $user    = User::factory()->create();
        $payload = array_merge($this->validPayload(), ['days_of_week' => []]);

        $this->actingAs($user)->post('/user/delivery', $payload);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'days_of_week' => '']);
    }

    public function test_update_accepts_null_days_of_week_and_stores_empty_string(): void
    {
        $user    = User::factory()->create();
        $payload = ['timezone' => 'UTC', 'delivery_time_local' => '0800'];

        $this->actingAs($user)->post('/user/delivery', $payload);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'days_of_week' => '']);
    }
}
