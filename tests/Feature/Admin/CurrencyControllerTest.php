<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_currency_index(): void
    {
        $this->getJson(
            route('admin.master-data.currencies.index')
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_currency_endpoints(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->getJson(route('admin.master-data.currencies.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->postJson(
                route('admin.master-data.currencies.store'),
                $this->validCurrencyPayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount('currencies', 0);
    }

    public function test_admin_can_list_currencies_sorted_by_sort_order_then_name(): void
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
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('admin.master-data.currencies.index'));

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.code', 'BDT')
            ->assertJsonPath('data.1.code', 'EUR')
            ->assertJsonPath('data.2.code', 'USD');
    }

    public function test_admin_can_create_currency(): void
    {
        $payload = $this->validCurrencyPayload();

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.currencies.store'),
                $payload
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Currency created successfully.'
            )
            ->assertJsonPath('data.name', 'Bangladeshi Taka')
            ->assertJsonPath('data.code', 'BDT')
            ->assertJsonPath('data.symbol', '৳')
            ->assertJsonPath('data.decimal_places', 2)
            ->assertJsonPath('data.sort_order', 10)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('currencies', $payload);
    }

    public function test_currency_creation_validates_required_and_formatted_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.currencies.store'),
                [
                    'name' => '',
                    'code' => 'usd',
                    'symbol' => str_repeat('x', 17),
                    'decimal_places' => 5,
                    'sort_order' => -1,
                    'is_active' => 'active',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
                'symbol',
                'decimal_places',
                'sort_order',
                'is_active',
            ]);

        $this->assertDatabaseCount('currencies', 0);
    }

    public function test_currency_creation_rejects_duplicate_name_and_code(): void
    {
        $this->createCurrency();

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.currencies.store'),
                $this->validCurrencyPayload([
                    'symbol' => 'Tk',
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
            ]);

        $this->assertDatabaseCount('currencies', 1);
    }

    public function test_admin_can_show_currency(): void
    {
        $currency = $this->createCurrency();

        $response = $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.currencies.show',
                    $currency
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $currency->id)
            ->assertJsonPath('data.name', 'Bangladeshi Taka')
            ->assertJsonPath('data.code', 'BDT');
    }

    public function test_admin_can_update_currency_and_keep_its_own_unique_values(): void
    {
        $currency = $this->createCurrency();

        $response = $this->actingAs($this->adminUser())
            ->patchJson(
                route(
                    'admin.master-data.currencies.update',
                    $currency
                ),
                $this->validCurrencyPayload([
                    'symbol' => 'Tk',
                    'decimal_places' => 0,
                    'sort_order' => 5,
                    'is_active' => false,
                ])
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Currency updated successfully.'
            )
            ->assertJsonPath('data.name', 'Bangladeshi Taka')
            ->assertJsonPath('data.code', 'BDT')
            ->assertJsonPath('data.symbol', 'Tk')
            ->assertJsonPath('data.decimal_places', 0)
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Bangladeshi Taka',
            'code' => 'BDT',
            'symbol' => 'Tk',
            'decimal_places' => 0,
            'sort_order' => 5,
            'is_active' => false,
        ]);
    }

    public function test_currency_update_rejects_name_and_code_used_by_another_currency(): void
    {
        $taka = $this->createCurrency();

        $this->createCurrency([
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->patchJson(
                route(
                    'admin.master-data.currencies.update',
                    $taka
                ),
                $this->validCurrencyPayload([
                    'name' => 'US Dollar',
                    'code' => 'USD',
                ])
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
            ]);

        $this->assertDatabaseHas('currencies', [
            'id' => $taka->id,
            'name' => 'Bangladeshi Taka',
            'code' => 'BDT',
        ]);
    }

    public function test_read_only_admin_can_view_but_cannot_mutate_currencies(): void
    {
        $currency = $this->createCurrency();
        $admin = $this->adminUser();

        Role::findByName('admin')->revokePermissionTo('master-data.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->getJson(route('admin.master-data.currencies.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->getJson(route('admin.master-data.currencies.show', $currency))
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(
                route('admin.master-data.currencies.store'),
                $this->validCurrencyPayload([
                    'name' => 'Euro',
                    'code' => 'EUR',
                ])
            )
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson(
                route('admin.master-data.currencies.update', $currency),
                $this->validCurrencyPayload([
                    'name' => 'Updated Taka',
                ])
            )
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson(route('admin.master-data.currencies.destroy', $currency))
            ->assertForbidden();

        $this->assertDatabaseCount('currencies', 1);
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Bangladeshi Taka',
        ]);
    }

    public function test_admin_can_delete_currency(): void
    {
        $currency = $this->createCurrency();

        $response = $this->actingAs($this->adminUser())
            ->deleteJson(
                route(
                    'admin.master-data.currencies.destroy',
                    $currency
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Currency deleted successfully.'
            );

        $this->assertDatabaseMissing('currencies', [
            'id' => $currency->id,
        ]);
    }

    public function test_missing_currency_returns_not_found(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.currencies.show',
                    999999
                )
            )
            ->assertNotFound();
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
        return Currency::query()->create(
            $this->validCurrencyPayload($overrides)
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCurrencyPayload(
        array $overrides = []
    ): array {
        return array_merge([
            'name' => 'Bangladeshi Taka',
            'code' => 'BDT',
            'symbol' => '৳',
            'decimal_places' => 2,
            'sort_order' => 10,
            'is_active' => true,
        ], $overrides);
    }
}
