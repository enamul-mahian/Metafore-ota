<?php

namespace Tests\Feature\Admin;

use App\Models\Affiliate;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AffiliateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $this->get(route('admin.affiliates.index'))->assertRedirect(route('login'));
        $customer = $this->user('customer');
        $this->actingAs($customer)->get(route('admin.affiliates.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.affiliates.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('affiliates', 0);
    }

    public function test_admin_and_super_admin_can_view_affiliates(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.affiliates.index'))
            ->assertOk()->assertSee('Affiliates')->assertSee('Create Affiliate');
        $this->actingAs($this->user('super-admin'))->get(route('admin.affiliates.index'))->assertOk();
    }

    public function test_admin_can_create_normalized_affiliate(): void
    {
        $country = $this->country();
        $this->actingAs($this->user('admin'))->post(route('admin.affiliates.store'), $this->payload([
            'name' => '  Arif Hasan  ', 'email' => ' PARTNER@EXAMPLE.COM ',
            'referral_code' => ' eagle-bd ', 'country_id' => $country->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('affiliates', [
            'name' => 'Arif Hasan', 'email' => 'partner@example.com',
            'referral_code' => 'EAGLE-BD', 'country_id' => $country->id,
            'website_url' => 'https://partner.example.com', 'status' => Affiliate::STATUS_ACTIVE,
        ]);
    }

    public function test_validation_and_unique_identifiers_are_enforced(): void
    {
        $this->actingAs($this->user('admin'))->post(route('admin.affiliates.store'), [
            'name' => '', 'email' => 'bad', 'phone' => 'abc', 'referral_code' => 'bad code',
            'website_url' => 'javascript:alert(1)', 'country_id' => 99999,
            'status' => 'approved', 'notes' => str_repeat('x', 5001),
        ])->assertSessionHasErrors([
            'name', 'email', 'phone', 'referral_code', 'website_url', 'country_id', 'status', 'notes',
        ]);

        $this->affiliate();
        $this->actingAs($this->user('admin'))->post(route('admin.affiliates.store'), $this->payload())
            ->assertSessionHasErrors(['email', 'referral_code']);
        $this->assertDatabaseCount('affiliates', 1);
    }

    public function test_admin_can_view_update_and_delete_affiliate(): void
    {
        $affiliate = $this->affiliate();
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('admin.affiliates.show', $affiliate))
            ->assertOk()->assertSee('Arif Hasan')->assertSee('EAGLE-BD')->assertSee('https://partner.example.com');

        $this->actingAs($admin)->patch(route('admin.affiliates.update', $affiliate), $this->payload([
            'name' => 'Arif Ahmed', 'status' => Affiliate::STATUS_INACTIVE, 'notes' => '',
        ]))->assertRedirect(route('admin.affiliates.show', $affiliate));
        $this->assertDatabaseHas('affiliates', [
            'id' => $affiliate->id, 'name' => 'Arif Ahmed', 'referral_code' => 'EAGLE-BD',
            'status' => Affiliate::STATUS_INACTIVE, 'notes' => null,
        ]);

        $this->actingAs($admin)->delete(route('admin.affiliates.destroy', $affiliate))
            ->assertRedirect(route('admin.affiliates.index'));
        $this->assertDatabaseMissing('affiliates', ['id' => $affiliate->id]);
    }

    public function test_update_rejects_another_affiliates_identifiers(): void
    {
        $affiliate = $this->affiliate();
        $this->affiliate(['name' => 'Other', 'email' => 'other@example.com', 'referral_code' => 'OTHER']);
        $this->actingAs($this->user('admin'))->patch(route('admin.affiliates.update', $affiliate), $this->payload([
            'email' => 'other@example.com', 'referral_code' => 'OTHER',
        ]))->assertSessionHasErrors(['email', 'referral_code']);
    }

    public function test_read_only_admin_can_view_but_cannot_mutate(): void
    {
        $affiliate = $this->affiliate();
        $admin = $this->user('admin');
        Role::findByName('admin')->revokePermissionTo('affiliates.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)->get(route('admin.affiliates.index'))->assertOk()->assertDontSee('Create Affiliate');
        $this->actingAs($admin)->get(route('admin.affiliates.show', $affiliate))->assertOk()->assertDontSee('Delete Affiliate');
        $this->actingAs($admin)->get(route('admin.affiliates.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.affiliates.store'), $this->payload())->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.affiliates.update', $affiliate), $this->payload())->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.affiliates.destroy', $affiliate))->assertForbidden();
    }

    public function test_filters_pagination_permissions_and_navigation_are_wired(): void
    {
        $this->affiliate(['name' => 'Inactive Target', 'email' => 'target@example.com', 'referral_code' => 'TARGET', 'status' => Affiliate::STATUS_INACTIVE]);
        $this->affiliate(['name' => 'Active Other', 'email' => 'other@example.com', 'referral_code' => 'OTHER']);
        $admin = $this->user('admin');
        $customer = $this->user('customer');

        $this->actingAs($admin)->get(route('admin.affiliates.index', ['search' => 'Target', 'status' => 'inactive']))
            ->assertOk()->assertSee('Inactive Target')->assertDontSee('Active Other');
        foreach (range(1, 15) as $number) {
            $this->affiliate(['name' => 'Paged '.$number, 'email' => 'page'.$number.'@example.com', 'referral_code' => 'PAGE-'.$number]);
        }
        $this->actingAs($admin)->get(route('admin.affiliates.index'))->assertOk()
            ->assertSee('page=2', false)->assertSee(route('admin.affiliates.index'), false);
        $this->actingAs($admin)->get(route('admin.settings.manage'))->assertOk()->assertSee(route('admin.affiliates.index'), false);
        $this->assertTrue($admin->can('affiliates.manage'));
        $this->assertFalse($customer->can('affiliates.view'));
    }

    public function test_missing_affiliate_returns_not_found(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.affiliates.show', 999999))->assertNotFound();
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
        return Country::query()->create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '+880', 'is_active' => true]);
    }

    /** @param array<string, mixed> $overrides */
    private function affiliate(array $overrides = []): Affiliate
    {
        return Affiliate::query()->create($this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Arif Hasan', 'email' => 'partner@example.com', 'phone' => '+880 1712-345678',
            'organization_name' => 'Partner Media', 'referral_code' => 'EAGLE-BD',
            'website_url' => 'https://partner.example.com', 'country_id' => null,
            'status' => Affiliate::STATUS_ACTIVE, 'notes' => 'Approved marketing contact.',
        ], $overrides);
    }
}
