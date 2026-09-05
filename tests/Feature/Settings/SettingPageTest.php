<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.settings.manage'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_settings_admin_page(): void
    {
        $user = $this->verifiedUserWithRole('customer');

        $this->actingAs($user)
            ->get(route('admin.settings.manage'))
            ->assertForbidden();
    }

    public function test_admin_can_view_settings_page_in_read_only_mode(): void
    {
        $user = $this->verifiedUserWithRole('admin');

        $this->actingAs($user)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Read only mode')
            ->assertDontSee('Add New Setting')
            ->assertDontSee('Add New Group');
    }

    public function test_super_admin_can_view_settings_page_with_management_access(): void
    {
        $user = $this->verifiedUserWithRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Add New Setting')
            ->assertDontSee('Add New Group')
            ->assertDontSee('Read only mode');
    }

    public function test_settings_page_does_not_render_mojibake_characters(): void
    {
        $user = $this->verifiedUserWithRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee('&rsaquo;', false)
            ->assertSee('&times;', false)
            ->assertSee('&copy; '.date('Y'), false)
            ->assertDontSee('Search anything...')
            ->assertDontSee('aria-label="Theme"', false)
            ->assertDontSee('aria-label="Notifications"', false)
            ->assertDontSee('notification-count', false)
            ->assertDontSee('Add New Group')
            ->assertDontSee('settings-pagination', false)
            ->assertDontSee("\u{00E2}\u{20AC}", false)
            ->assertDontSee("\u{00EF}\u{00BC}", false)
            ->assertDontSee("\u{00C3}\u{2014}", false)
            ->assertDontSee("\u{00C2}\u{00A9}", false);
    }

    private function verifiedUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        return $user;
    }
}
