<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_users_index(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect();
    }

    public function test_customer_cannot_access_users_index(): void
    {
        $this->actingAs($this->userWithRole('customer'))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_users_index(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_admin_can_create_customer(): void
    {
        $admin = $this->userWithRole('admin');
        $password = Str::password(24);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Customer Example',
                'email' => 'customer@example.test',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => 'customer',
            ])
            ->assertRedirect();

        $created = User::query()
            ->where('email', 'customer@example.test')
            ->firstOrFail();

        $this->assertTrue($created->hasRole('customer'));
    }

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $admin = $this->userWithRole('admin');
        $password = Str::password(24);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Blocked Example',
                'email' => 'blocked@example.test',
                'password' => $password,
                'password_confirmation' => $password,
                'role' => 'super-admin',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.test',
        ]);
    }

    public function test_admin_cannot_manage_super_admin_account(): void
    {
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $superAdmin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertForbidden();
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'customer',
            ])
            ->assertStatus(422);

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        return $user;
    }
}
