<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('account.profile'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_user_is_redirected_to_email_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('account.profile'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_sees_profile_and_password_forms(): void
    {
        $user = User::factory()->create([
            'name' => 'Profile Page User',
            'email' => 'profile-page@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('account.profile'))
            ->assertOk()
            ->assertSee('Profile Information')
            ->assertSee('value="Profile Page User"', false)
            ->assertSee('value="profile-page@example.com"', false)
            ->assertSee(route('user-profile-information.update'), false)
            ->assertSee(route('user-password.update'), false)
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_profile_update_redirects_back_with_status(): void
    {
        $user = User::factory()->create([
            'email' => 'profile-status@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Renamed Customer',
                'email' => 'profile-status@example.com',
            ])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHas('status', 'profile-information-updated');

        $this->assertSame('Renamed Customer', $user->fresh()->name);

        $this->actingAs($user->fresh())
            ->get(route('account.profile'))
            ->assertSee('Profile information updated.');
    }

    public function test_customer_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->put(route('user-password.update'), [
                'current_password' => 'old-password',
                'password' => 'New-Password-2026!',
                'password_confirmation' => 'New-Password-2026!',
            ])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(Hash::check('New-Password-2026!', $user->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->put(route('user-password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'New-Password-2026!',
                'password_confirmation' => 'New-Password-2026!',
            ])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHasErrors(['current_password'], null, 'updatePassword');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_guest_cannot_update_password(): void
    {
        $this->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'New-Password-2026!',
            'password_confirmation' => 'New-Password-2026!',
        ])->assertRedirect(route('login'));
    }
}
