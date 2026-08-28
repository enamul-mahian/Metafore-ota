<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_route(): void
    {
        $this->get(route('admin.access-test'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('admin.access-test'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_route(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.access-test'))
            ->assertOk()
            ->assertJson([
                'message' => 'Authorized',
            ]);
    }

    public function test_super_admin_can_access_admin_route(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.access-test'))
            ->assertOk()
            ->assertJson([
                'message' => 'Authorized',
            ]);
    }

    public function test_customer_permission_baseline_is_correct(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $this->assertTrue(
            $user->can('dashboard.view')
        );

        $this->assertFalse(
            $user->can('users.manage')
        );
    }

    public function test_customer_cannot_access_admin_role_group(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('admin.role-test'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_role_group(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.role-test'))
            ->assertOk()
            ->assertJson([
                'message' => 'Admin role authorized',
            ]);
    }

    public function test_super_admin_can_access_admin_role_group(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.role-test'))
            ->assertOk()
            ->assertJson([
                'message' => 'Admin role authorized',
            ]);
    }
}