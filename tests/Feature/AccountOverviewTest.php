<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Verified');
    }
}
