<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CurrencyPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_currencies_page(): void
    {
        $this->get(route('admin.currencies.manage'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_view_currencies_page(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('admin.currencies.manage'))
            ->assertForbidden();
    }

    public function test_admin_can_view_currencies_page_with_management_controls(): void
    {
        $this->createCurrency();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.currencies.manage'));

        $response
            ->assertOk()
            ->assertSee('Currencies')
            ->assertSee('Add Currency')
            ->assertSee('currency-form', false)
            ->assertSee('class="cur-edit"', false)
            ->assertSee('class="cur-delete"', false)
            ->assertSee('Bangladeshi Taka')
            ->assertSee('BDT');
    }

    public function test_super_admin_can_view_currencies_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.currencies.manage'))
            ->assertOk()
            ->assertSee('Add Currency');
    }

    public function test_currencies_are_rendered_in_sort_order_then_name(): void
    {
        $this->createCurrency([
            'name' => 'US Dollar',
            'code' => 'USD',
            'sort_order' => 20,
        ]);

        $this->createCurrency([
            'name' => 'Euro',
            'code' => 'EUR',
            'sort_order' => 10,
        ]);

        $this->createCurrency([
            'name' => 'Bangladeshi Taka',
            'code' => 'BDT',
            'sort_order' => 10,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.currencies.manage'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Bangladeshi Taka',
                'Euro',
                'US Dollar',
            ])
            ->assertSee('Inactive')
            ->assertSee('Active');
    }

    public function test_admin_without_manage_permission_gets_read_only_currencies_page(): void
    {
        $admin = $this->adminUser();
        Role::findByName('admin')->revokePermissionTo('master-data.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($admin)
            ->get(route('admin.currencies.manage'));

        $response
            ->assertOk()
            ->assertSee('Currencies')
            ->assertDontSee('Add Currency')
            ->assertDontSee('currency-form', false)
            ->assertDontSee('class="cur-edit"', false)
            ->assertDontSee('class="cur-delete"', false);
    }

    public function test_admin_sidebars_contain_currencies_link(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('admin.currencies.manage'))
            ->assertOk()
            ->assertSee(route('admin.currencies.manage'), false)
            ->assertSee('Currencies');

        $this->actingAs($admin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee(route('admin.currencies.manage'), false);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();

        $user->assignRole('admin');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCurrency(
        array $overrides = []
    ): Currency {
        return Currency::query()->create(array_merge([
            'name' => 'Bangladeshi Taka',
            'code' => 'BDT',
            'symbol' => '৳',
            'decimal_places' => 2,
            'sort_order' => 10,
            'is_active' => true,
        ], $overrides));
    }
}
