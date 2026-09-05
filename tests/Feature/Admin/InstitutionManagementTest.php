<?php

namespace Tests\Feature\Admin;

use App\Models\Country;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstitutionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_redirected_customer_forbidden_and_admin_roles_allowed(): void
    {
        $this->get(route('admin.institutions.index'))->assertRedirect(route('login'));
        $customer = $this->user('customer');
        $this->actingAs($customer)->get(route('admin.institutions.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.institutions.store'), $this->payload())->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('admin.institutions.index'))->assertOk()->assertSee('Create Institution');
        $this->actingAs($this->user('super-admin'))->get(route('admin.institutions.index'))->assertOk();
    }

    public function test_admin_can_create_normalized_institution_with_country(): void
    {
        $country = $this->country();
        $this->actingAs($this->user('admin'))->post(route('admin.institutions.store'), $this->payload([
            'name' => '  Example University ', 'email' => ' INFO@EXAMPLE.EDU ',
            'country_id' => $country->id,
        ]))->assertRedirect();
        $this->assertDatabaseHas('institutions', [
            'name' => 'Example University', 'email' => 'info@example.edu',
            'registration_number' => 'EDU-1001', 'country_id' => $country->id,
            'status' => Institution::STATUS_ACTIVE,
        ]);
    }

    public function test_validation_and_optional_unique_contacts_are_enforced(): void
    {
        $this->actingAs($this->user('admin'))->post(route('admin.institutions.store'), [
            'name' => '', 'email' => 'bad', 'phone' => 'abc', 'website_url' => 'javascript:alert(1)',
            'registration_number' => str_repeat('x', 101), 'country_id' => 99999,
            'address' => str_repeat('x', 501), 'status' => 'pending', 'notes' => str_repeat('x', 5001),
        ])->assertSessionHasErrors(['name', 'email', 'phone', 'website_url', 'registration_number', 'country_id', 'address', 'status', 'notes']);
        $this->institution();
        $this->actingAs($this->user('admin'))->post(route('admin.institutions.store'), $this->payload())
            ->assertSessionHasErrors(['email', 'registration_number']);
    }

    public function test_admin_can_show_update_and_delete_institution(): void
    {
        $institution = $this->institution();
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.institutions.show', $institution))
            ->assertOk()->assertSee('Example University')->assertSee('EDU-1001')->assertSee('Dhaka campus');
        $this->actingAs($admin)->patch(route('admin.institutions.update', $institution), $this->payload([
            'name' => 'Example Institute', 'status' => Institution::STATUS_ARCHIVED, 'notes' => '',
        ]))->assertRedirect(route('admin.institutions.show', $institution));
        $this->assertDatabaseHas('institutions', ['id' => $institution->id, 'name' => 'Example Institute', 'status' => 'archived', 'notes' => null]);
        $this->actingAs($admin)->delete(route('admin.institutions.destroy', $institution))->assertRedirect(route('admin.institutions.index'));
        $this->assertDatabaseMissing('institutions', ['id' => $institution->id]);
    }

    public function test_read_only_admin_can_view_but_cannot_mutate(): void
    {
        $institution = $this->institution();
        $admin = $this->user('admin');
        Role::findByName('admin')->revokePermissionTo('institutions.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($admin)->get(route('admin.institutions.index'))->assertOk()->assertDontSee('Create Institution');
        $this->actingAs($admin)->get(route('admin.institutions.show', $institution))->assertOk()->assertDontSee('Delete Institution');
        $this->actingAs($admin)->get(route('admin.institutions.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.institutions.store'), $this->payload())->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.institutions.update', $institution), $this->payload())->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.institutions.destroy', $institution))->assertForbidden();
    }

    public function test_search_filter_order_pagination_navigation_and_permissions(): void
    {
        $older = $this->institution(['name' => 'Older College', 'email' => 'older@example.edu', 'registration_number' => 'OLD-1']);
        $newer = $this->institution(['name' => 'Target Academy', 'email' => 'target@example.edu', 'registration_number' => 'TARGET-1', 'status' => 'archived']);
        $admin = $this->user('admin');
        $customer = $this->user('customer');
        $this->actingAs($admin)->get(route('admin.institutions.index', ['search' => 'Target', 'status' => 'archived']))
            ->assertOk()->assertSee('Target Academy')->assertDontSee('Older College');
        $this->actingAs($admin)->get(route('admin.institutions.index'))->assertOk()->assertSeeInOrder([$newer->name, $older->name]);
        foreach (range(1, 14) as $number) {
            $this->institution(['name' => 'Paged '.$number, 'email' => 'page'.$number.'@example.edu', 'registration_number' => 'PAGE-'.$number]);
        }
        $this->actingAs($admin)->get(route('admin.institutions.index'))->assertOk()->assertSee('page=2', false)->assertSee(route('admin.institutions.index'), false);
        $this->actingAs($admin)->get(route('admin.settings.manage'))->assertOk()->assertSee(route('admin.institutions.index'), false);
        $this->assertTrue($admin->can('institutions.manage'));
        $this->assertFalse($customer->can('institutions.view'));
    }

    public function test_schema_omits_speculative_admission_assignment_contract_and_finance_fields(): void
    {
        foreach (['student_id', 'admission_status', 'contract_status', 'commission', 'tuition_fee', 'balance', 'document_path'] as $column) {
            $this->assertFalse(Schema::hasColumn('institutions', $column));
        }
    }

    public function test_missing_institution_returns_not_found(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.institutions.show', 999999))->assertNotFound();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function country(): Country
    {
        return Country::query()->create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '+880', 'is_active' => true]);
    }

    /** @param array<string, mixed> $overrides */
    private function institution(array $overrides = []): Institution
    {
        return Institution::query()->create($this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Example University', 'email' => 'info@example.edu', 'phone' => '+880 1712-345678',
            'website_url' => 'https://example.edu', 'registration_number' => 'EDU-1001', 'country_id' => null,
            'address' => 'Dhaka campus', 'status' => Institution::STATUS_ACTIVE, 'notes' => 'Primary contact profile.',
        ], $overrides);
    }
}
