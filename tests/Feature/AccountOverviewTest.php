<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_account_overview(): void
    {
        $this->get(route('account.overview'))
            ->assertRedirect(route('login'));
    }

    public function test_unverified_user_is_redirected_to_email_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('account.overview'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_view_account_overview(): void
    {
        $user = User::factory()->create([
            'name' => 'Account Test User',
            'email' => 'account-test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('account.overview'))
            ->assertOk()
            ->assertSee('Account Overview')
            ->assertSee('Account Test User')
            ->assertSee('account-test@example.com')
            ->assertSee('Save Profile')
            ->assertSee(route('user-profile-information.update'), false)
            ->assertSee('Verified');
    }

    public function test_guest_cannot_update_profile_information(): void
    {
        $this->put(route('user-profile-information.update'), [
            'name' => 'Guest Update',
            'email' => 'guest-update@example.com',
        ])->assertRedirect(route('login'));
    }

    public function test_customer_can_update_name_without_losing_verification(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'profile@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('account.overview'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Updated Customer',
                'email' => 'profile@example.com',
            ])
            ->assertRedirect(route('account.overview'))
            ->assertSessionHas('status', 'profile-information-updated');

        $user->refresh();

        $this->assertSame('Updated Customer', $user->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_changing_email_requires_verification_of_normalized_address(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old-profile@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('account.overview'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Email Change User',
                'email' => 'NEW-PROFILE@EXAMPLE.COM',
            ])
            ->assertRedirect(route('account.overview'));

        $user->refresh();

        $this->assertSame('new-profile@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get(route('account.overview'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_profile_update_rejects_invalid_and_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing-profile@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'current-profile@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('account.overview'))
            ->put(route('user-profile-information.update'), [
                'name' => '',
                'email' => 'existing-profile@example.com',
            ])
            ->assertRedirect(route('account.overview'))
            ->assertSessionHasErrors(
                ['name', 'email'],
                null,
                'updateProfileInformation',
            );

        $this->assertSame(
            'current-profile@example.com',
            $user->fresh()->email,
        );
    }
}
