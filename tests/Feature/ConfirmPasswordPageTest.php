<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfirmPasswordPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_password_confirmation_page(): void
    {
        $this->get(route('password.confirm'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_password_confirmation_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('Confirm Password')
            ->assertSee('confirm your password', false);
    }

    public function test_authenticated_user_can_confirm_their_password(): void
    {
        $password = 'Local-Confirm-Password-Test-2026!';

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ]);

        $response = $this->actingAs($user)
            ->post(route('password.confirm.store'), [
                'password' => $password,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $this->assertNotNull(
            session('auth.password_confirmed_at')
        );
    }
}
