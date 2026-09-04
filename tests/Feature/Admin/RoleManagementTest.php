<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_roles_index(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_customer_cannot_access_roles_index(): void
    {
        $this->actingAs($this->userWithRole('customer'))
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_manage_roles(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.roles.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'support-agent',
                'permissions' => ['users.view'],
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_create_and_update_custom_role(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'support-agent',
                'permissions' => ['users.view'],
            ])
            ->assertRedirect();

        $role = Role::findByName('support-agent', 'web');
        $this->assertTrue($role->hasPermissionTo('users.view'));

        $this->actingAs($superAdmin)
            ->patch(route('admin.roles.update', $role), [
                'name' => 'support-agent',
                'permissions' => ['users.view', 'roles.view'],
            ])
            ->assertRedirect();

        $this->assertTrue($role->fresh()->hasPermissionTo('roles.view'));
    }

    public function test_super_admin_role_cannot_be_edited(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $role = Role::findByName('super-admin', 'web');

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.edit', $role))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->patch(route('admin.roles.update', $role), [
                'name' => 'super-admin',
                'permissions' => [],
            ])
            ->assertForbidden();
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $role = Role::findByName('admin', 'web');

        $this->actingAs($superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_assigned_custom_role_cannot_be_deleted(): void
    {
        $superAdmin = $this->userWithRole('super-admin');
        $role = Role::create(['name' => 'assigned-role', 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $this->actingAs($superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertStatus(422);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }
}
