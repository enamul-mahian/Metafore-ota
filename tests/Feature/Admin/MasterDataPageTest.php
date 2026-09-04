<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MasterDataPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_master_data_page(): void
    {
        $this->get(route('admin.master-data.manage'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_master_data_page(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)
            ->get(route('admin.master-data.manage'))
            ->assertForbidden();
    }

    public function test_admin_can_view_master_data_page_and_management_controls(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.master-data.manage'))
            ->assertOk()
            ->assertSee('Master Data')
            ->assertSee('Countries')
            ->assertSee('Cities')
            ->assertSee('id="country-form"', false)
            ->assertSee('id="city-form"', false)
            ->assertSee(route('admin.master-data.manage'), false);
    }

    public function test_super_admin_can_view_master_data_page(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.master-data.manage'))
            ->assertOk()
            ->assertSee('Master Data')
            ->assertSee('Countries')
            ->assertSee('Cities');
    }

    public function test_country_and_city_records_are_rendered(): void
    {
        $admin = $this->userWithRole('admin');

        $country = Country::query()->create([
            'name' => 'Bangladesh',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
            'is_active' => true,
        ]);

        City::query()->create([
            'country_id' => $country->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
            'timezone' => 'Asia/Dhaka',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.master-data.manage'))
            ->assertOk()
            ->assertSee('Bangladesh')
            ->assertSee('BD / BGD')
            ->assertSee('Dhaka')
            ->assertSee('DAC');
    }

    public function test_admin_without_manage_permission_gets_read_only_page(): void
    {
        $adminRole = Role::findByName('admin', 'web');
        $adminRole->revokePermissionTo('master-data.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.master-data.manage'))
            ->assertOk()
            ->assertSee('Master Data')
            ->assertDontSee('id="country-form"', false)
            ->assertDontSee('id="city-form"', false)
            ->assertDontSee('class="md-delete"', false);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
