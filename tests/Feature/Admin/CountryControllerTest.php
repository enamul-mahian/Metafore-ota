<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_country_index(): void
    {
        $this->getJson(
            route('admin.master-data.countries.index')
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_country_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->getJson(route('admin.master-data.countries.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_create_country(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->postJson(
                route('admin.master-data.countries.store'),
                $this->validCountryPayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount('countries', 0);
    }

    public function test_admin_can_list_countries_sorted_by_name(): void
    {
        $this->createCountry([
            'name' => 'Zimbabwe',
            'iso2' => 'ZW',
            'iso3' => 'ZWE',
            'phone_code' => '+263',
        ]);

        $this->createCountry([
            'name' => 'Bangladesh',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('admin.master-data.countries.index'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Bangladesh')
            ->assertJsonPath('data.1.name', 'Zimbabwe');
    }

    public function test_admin_can_create_country(): void
    {
        $payload = $this->validCountryPayload();

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.countries.store'),
                $payload
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Country created successfully.'
            )
            ->assertJsonPath('data.name', 'Bangladesh')
            ->assertJsonPath('data.iso2', 'BD')
            ->assertJsonPath('data.iso3', 'BGD')
            ->assertJsonPath('data.phone_code', '+880')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('countries', [
            'name' => 'Bangladesh',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
            'is_active' => true,
        ]);
    }

    public function test_country_creation_validates_required_and_formatted_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.countries.store'),
                [
                    'name' => '',
                    'iso2' => 'bd',
                    'iso3' => 'bgd',
                    'phone_code' => '+88A',
                    'is_active' => 'active',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'iso2',
                'iso3',
                'phone_code',
                'is_active',
            ]);

        $this->assertDatabaseCount('countries', 0);
    }

    public function test_country_creation_rejects_duplicate_iso_codes(): void
    {
        $this->createCountry([
            'name' => 'Bangladesh',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.countries.store'),
                [
                    'name' => 'Duplicate Bangladesh',
                    'iso2' => 'BD',
                    'iso3' => 'BGD',
                    'phone_code' => '+880',
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'iso2',
                'iso3',
            ]);

        $this->assertDatabaseCount('countries', 1);
    }

    public function test_admin_can_show_country(): void
    {
        $country = $this->createCountry();

        $response = $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.countries.show',
                    $country
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $country->id)
            ->assertJsonPath('data.name', 'Bangladesh')
            ->assertJsonPath('data.iso2', 'BD')
            ->assertJsonPath('data.iso3', 'BGD');
    }

    public function test_admin_can_update_country_and_keep_its_own_iso_codes(): void
    {
        $country = $this->createCountry();

        $response = $this->actingAs($this->adminUser())
            ->putJson(
                route(
                    'admin.master-data.countries.update',
                    $country
                ),
                [
                    'name' => 'Bangladesh Updated',
                    'iso2' => 'BD',
                    'iso3' => 'BGD',
                    'phone_code' => '+880',
                    'is_active' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Country updated successfully.'
            )
            ->assertJsonPath(
                'data.name',
                'Bangladesh Updated'
            )
            ->assertJsonPath('data.iso2', 'BD')
            ->assertJsonPath('data.iso3', 'BGD')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Bangladesh Updated',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
            'is_active' => false,
        ]);
    }

    public function test_country_update_rejects_iso_codes_used_by_another_country(): void
    {
        $bangladesh = $this->createCountry();

        $this->createCountry([
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phone_code' => '+91',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->putJson(
                route(
                    'admin.master-data.countries.update',
                    $bangladesh
                ),
                [
                    'name' => 'Bangladesh',
                    'iso2' => 'IN',
                    'iso3' => 'IND',
                    'phone_code' => '+880',
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'iso2',
                'iso3',
            ]);

        $this->assertDatabaseHas('countries', [
            'id' => $bangladesh->id,
            'iso2' => 'BD',
            'iso3' => 'BGD',
        ]);
    }

    public function test_admin_can_delete_country(): void
    {
        $country = $this->createCountry();

        $response = $this->actingAs($this->adminUser())
            ->deleteJson(
                route(
                    'admin.master-data.countries.destroy',
                    $country
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Country deleted successfully.'
            );

        $this->assertDatabaseMissing('countries', [
            'id' => $country->id,
        ]);
    }

    public function test_country_with_cities_cannot_be_deleted(): void
    {
        $country = $this->createCountry();

        City::query()->create([
            'country_id' => $country->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
            'timezone' => 'Asia/Dhaka',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->deleteJson(
                route(
                    'admin.master-data.countries.destroy',
                    $country
                )
            );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Country cannot be deleted while cities are associated with it.'
            );

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
        ]);

        $this->assertDatabaseHas('cities', [
            'country_id' => $country->id,
            'name' => 'Dhaka',
        ]);
    }

    public function test_missing_country_returns_not_found(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.countries.show',
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
     * @param array<string, mixed> $overrides
     */
    private function createCountry(
        array $overrides = []
    ): Country {
        return Country::query()->create(
            array_merge(
                $this->validCountryPayload(),
                $overrides
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validCountryPayload(): array
    {
        return [
            'name' => 'Bangladesh',
            'iso2' => 'BD',
            'iso3' => 'BGD',
            'phone_code' => '+880',
            'is_active' => true,
        ];
    }
}