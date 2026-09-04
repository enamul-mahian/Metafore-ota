<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AgentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $this->get(route('admin.agents.index'))->assertRedirect(route('login'));

        $customer = $this->user('customer');
        $this->actingAs($customer)
            ->get(route('admin.agents.index'))
            ->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.agents.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_admin_and_super_admin_can_view_agents(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('admin.agents.index'))
            ->assertOk()
            ->assertSee('Agents')
            ->assertSee('Create Agent');

        $this->actingAs($this->user('super-admin'))
            ->get(route('admin.agents.index'))
            ->assertOk();
    }

    public function test_admin_can_create_normalized_agent_profile(): void
    {
        $country = $this->country();

        $this->actingAs($this->user('admin'))
            ->post(route('admin.agents.store'), $this->payload([
                'name' => '  Samira Khan  ',
                'email' => '  AGENT@EXAMPLE.COM  ',
                'country_id' => $country->id,
            ]))
            ->assertRedirect();

        $agent = Agent::query()->firstOrFail();
        $this->assertSame('Samira Khan', $agent->name);
        $this->assertSame('agent@example.com', $agent->email);
        $this->assertSame($country->id, $agent->country_id);
        $this->assertSame(Agent::STATUS_ACTIVE, $agent->status);
        $this->assertDatabaseHas('agents', [
            'company_name' => 'Eagle Partner Services',
            'registration_number' => 'REG-1001',
            'notes' => 'Primary operational contact.',
        ]);
    }

    public function test_agent_validation_rejects_invalid_fields(): void
    {
        $this->actingAs($this->user('admin'))
            ->post(route('admin.agents.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'phone' => 'abc',
                'company_name' => str_repeat('x', 151),
                'registration_number' => str_repeat('x', 101),
                'country_id' => 999999,
                'status' => 'approved',
                'notes' => str_repeat('x', 5001),
            ])
            ->assertSessionHasErrors([
                'name',
                'email',
                'phone',
                'company_name',
                'registration_number',
                'country_id',
                'status',
                'notes',
            ]);

        $this->assertDatabaseCount('agents', 0);
    }

    public function test_agent_email_and_registration_number_are_unique(): void
    {
        $this->agent();

        $this->actingAs($this->user('admin'))
            ->post(route('admin.agents.store'), $this->payload())
            ->assertSessionHasErrors(['email', 'registration_number']);

        $this->assertDatabaseCount('agents', 1);
    }

    public function test_admin_can_view_and_update_agent_with_existing_unique_values(): void
    {
        $agent = $this->agent();

        $this->actingAs($this->user('admin'))
            ->get(route('admin.agents.show', $agent))
            ->assertOk()
            ->assertSee('Samira Khan')
            ->assertSee('agent@example.com')
            ->assertSee('Eagle Partner Services')
            ->assertSee('Primary operational contact.');

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.agents.update', $agent), $this->payload([
                'name' => 'Samira Ahmed',
                'status' => Agent::STATUS_INACTIVE,
                'notes' => '',
            ]))
            ->assertRedirect(route('admin.agents.show', $agent));

        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'name' => 'Samira Ahmed',
            'email' => 'agent@example.com',
            'registration_number' => 'REG-1001',
            'status' => Agent::STATUS_INACTIVE,
            'notes' => null,
        ]);
    }

    public function test_update_rejects_identifiers_owned_by_another_agent(): void
    {
        $agent = $this->agent();
        $this->agent([
            'name' => 'Other Agent',
            'email' => 'other@example.com',
            'registration_number' => 'REG-2002',
        ]);

        $this->actingAs($this->user('admin'))
            ->patch(route('admin.agents.update', $agent), $this->payload([
                'email' => 'other@example.com',
                'registration_number' => 'REG-2002',
            ]))
            ->assertSessionHasErrors(['email', 'registration_number']);
    }

    public function test_admin_can_delete_agent_profile(): void
    {
        $agent = $this->agent();

        $this->actingAs($this->user('admin'))
            ->delete(route('admin.agents.destroy', $agent))
            ->assertRedirect(route('admin.agents.index'))
            ->assertSessionHas('status', 'Agent deleted successfully.');

        $this->assertDatabaseMissing('agents', ['id' => $agent->id]);
    }

    public function test_read_only_admin_can_view_but_cannot_mutate_agents(): void
    {
        $agent = $this->agent();
        $admin = $this->user('admin');
        Role::findByName('admin')->revokePermissionTo('agents.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->get(route('admin.agents.index'))
            ->assertOk()
            ->assertDontSee('Create Agent');
        $this->actingAs($admin)
            ->get(route('admin.agents.show', $agent))
            ->assertOk()
            ->assertDontSee('Delete Agent');
        $this->actingAs($admin)
            ->get(route('admin.agents.create'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('admin.agents.store'), $this->payload(['email' => 'new@example.com']))
            ->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('admin.agents.update', $agent), $this->payload())
            ->assertForbidden();
        $this->actingAs($admin)
            ->delete(route('admin.agents.destroy', $agent))
            ->assertForbidden();

        $this->assertDatabaseCount('agents', 1);
    }

    public function test_agent_index_supports_search_status_and_pagination(): void
    {
        $this->agent([
            'name' => 'Inactive Search Target',
            'email' => 'target@example.com',
            'registration_number' => 'TARGET-1',
            'status' => Agent::STATUS_INACTIVE,
        ]);
        $this->agent([
            'name' => 'Active Other',
            'email' => 'other@example.com',
            'registration_number' => 'OTHER-1',
            'status' => Agent::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.agents.index', [
                'search' => 'Search Target',
                'status' => Agent::STATUS_INACTIVE,
            ]))
            ->assertOk()
            ->assertSee('Inactive Search Target')
            ->assertDontSee('Active Other');

        foreach (range(1, 15) as $number) {
            $this->agent([
                'name' => 'Paged Agent '.$number,
                'email' => 'paged'.$number.'@example.com',
                'registration_number' => 'PAGE-'.$number,
            ]);
        }

        $this->actingAs($this->user('admin'))
            ->get(route('admin.agents.index'))
            ->assertOk()
            ->assertSee('page=2', false);
    }

    public function test_navigation_and_permissions_are_wired_without_customer_access(): void
    {
        $admin = $this->user('admin');
        $customer = $this->user('customer');

        $this->assertTrue($admin->can('agents.view'));
        $this->assertTrue($admin->can('agents.manage'));
        $this->assertFalse($customer->can('agents.view'));

        $this->actingAs($admin)
            ->get(route('admin.agents.index'))
            ->assertOk()
            ->assertSee(route('admin.agents.index'), false);
        $this->actingAs($admin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee(route('admin.agents.index'), false);
    }

    public function test_missing_agent_returns_not_found(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('admin.agents.show', 999999))
            ->assertNotFound();
    }

    /** @param array<string, mixed> $attributes */
    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    private function country(): Country
    {
        return Country::query()->create([
            'name' => 'Bangladesh',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
            'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function agent(array $overrides = []): Agent
    {
        return Agent::query()->create($this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Samira Khan',
            'email' => 'agent@example.com',
            'phone' => '+880 1712-345678',
            'company_name' => 'Eagle Partner Services',
            'registration_number' => 'REG-1001',
            'country_id' => null,
            'status' => Agent::STATUS_ACTIVE,
            'notes' => 'Primary operational contact.',
        ], $overrides);
    }
}
