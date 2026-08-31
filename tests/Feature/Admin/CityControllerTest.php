<?php

namespace Tests\Feature\Admin;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_city_index(): void
    {
        $this->getJson(
            route('admin.master-data.cities.index')
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_city_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->getJson(route('admin.master-data.cities.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_create_city(): void
    {
        $country = $this->createCountry();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->postJson(
                route('admin.master-data.cities.store'),
                $this->validCityPayload($country)
            )
            ->assertForbidden();

        $this->assertDatabaseCount('cities', 0);
    }

    public function test_admin_can_list_cities_sorted_by_name_with_country(): void
    {
        $country = $this->createCountry();

        $this->createCity($country, [
            'name' => 'Dhaka',
            'code' => 'DAC',
        ]);

        $this->createCity($country, [
            'name' => 'Chattogram',
            'code' => 'CGP',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('admin.master-data.cities.index'));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Chattogram')
            ->assertJsonPath('data.0.country.name', 'Bangladesh')
            ->assertJsonPath('data.1.name', 'Dhaka')
            ->assertJsonPath('data.1.country.iso2', 'BD');
    }

    public function test_admin_can_create_city(): void
    {
        $country = $this->createCountry();

        $payload = $this->validCityPayload($country);

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.cities.store'),
                $payload
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'City created successfully.'
            )
            ->assertJsonPath('data.name', 'Dhaka')
            ->assertJsonPath('data.code', 'DAC')
            ->assertJsonPath('data.timezone', 'Asia/Dhaka')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.country.id', $country->id)
            ->assertJsonPath(
                'data.country.name',
                'Bangladesh'
            );

        $this->assertDatabaseHas('cities', [
            'country_id' => $country->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
            'timezone' => 'Asia/Dhaka',
            'is_active' => true,
        ]);
    }

    public function test_city_creation_validates_required_and_formatted_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.cities.store'),
                [
                    'country_id' => 999999,
                    'name' => '',
                    'code' => 'dh',
                    'timezone' => 'Invalid/Timezone',
                    'is_active' => 'active',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'country_id',
                'name',
                'code',
                'timezone',
                'is_active',
            ]);

        $this->assertDatabaseCount('cities', 0);
    }

    public function test_city_creation_rejects_duplicate_name_within_same_country(): void
    {
        $country = $this->createCountry();

        $this->createCity($country);

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.cities.store'),
                [
                    'country_id' => $country->id,
                    'name' => 'Dhaka',
                    'code' => 'DHK',
                    'timezone' => 'Asia/Dhaka',
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);

        $this->assertDatabaseCount('cities', 1);
    }

    public function test_same_city_name_is_allowed_in_different_countries(): void
    {
        $bangladesh = $this->createCountry();

        $india = $this->createCountry([
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phone_code' => '+91',
        ]);

        $this->createCity($bangladesh);

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.cities.store'),
                [
                    'country_id' => $india->id,
                    'name' => 'Dhaka',
                    'code' => 'DHI',
                    'timezone' => 'Asia/Kolkata',
                    'is_active' => true,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Dhaka')
            ->assertJsonPath(
                'data.country.id',
                $india->id
            );

        $this->assertDatabaseCount('cities', 2);
    }

    public function test_city_creation_rejects_duplicate_code(): void
    {
        $country = $this->createCountry();

        $this->createCity($country);

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.cities.store'),
                [
                    'country_id' => $country->id,
                    'name' => 'Chattogram',
                    'code' => 'DAC',
                    'timezone' => 'Asia/Dhaka',
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);

        $this->assertDatabaseCount('cities', 1);
    }

    public function test_admin_can_show_city_with_country(): void
    {
        $country = $this->createCountry();
        $city = $this->createCity($country);

        $response = $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.cities.show',
                    $city
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $city->id)
            ->assertJsonPath('data.name', 'Dhaka')
            ->assertJsonPath('data.code', 'DAC')
            ->assertJsonPath(
                'data.country.id',
                $country->id
            )
            ->assertJsonPath(
                'data.country.name',
                'Bangladesh'
            );
    }

    public function test_admin_can_update_city_and_keep_its_own_unique_values(): void
    {
        $country = $this->createCountry();
        $city = $this->createCity($country);

        $response = $this->actingAs($this->adminUser())
            ->putJson(
                route(
                    'admin.master-data.cities.update',
                    $city
                ),
                [
                    'country_id' => $country->id,
                    'name' => 'Dhaka',
                    'code' => 'DAC',
                    'timezone' => 'Asia/Dhaka',
                    'is_active' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'City updated successfully.'
            )
            ->assertJsonPath('data.name', 'Dhaka')
            ->assertJsonPath('data.code', 'DAC')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath(
                'data.country.id',
                $country->id
            );

        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'country_id' => $country->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
            'timezone' => 'Asia/Dhaka',
            'is_active' => false,
        ]);
    }

    public function test_city_update_rejects_name_used_by_another_city_in_same_country(): void
    {
        $country = $this->createCountry();

        $dhaka = $this->createCity($country);

        $this->createCity($country, [
            'name' => 'Chattogram',
            'code' => 'CGP',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->putJson(
                route(
                    'admin.master-data.cities.update',
                    $dhaka
                ),
                [
                    'country_id' => $country->id,
                    'name' => 'Chattogram',
                    'code' => 'DAC',
                    'timezone' => 'Asia/Dhaka',
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);

        $this->assertDatabaseHas('cities', [
            'id' => $dhaka->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
        ]);
    }

    public function test_city_update_rejects_code_used_by_another_city(): void
    {
        $country = $this->createCountry();

        $dhaka = $this->createCity($country);

        $this->createCity($country, [
            'name' => 'Chattogram',
            'code' => 'CGP',
        ]);

        $response = $this->actingAs($this->adminUser())
            ->putJson(
                route(
                    'admin.master-data.cities.update',
                    $dhaka
                ),
                [
                    'country_id' => $country->id,
                    'name' => 'Dhaka',
                    'code' => 'CGP',
                    'timezone' => 'Asia/Dhaka',
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);

        $this->assertDatabaseHas('cities', [
            'id' => $dhaka->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
        ]);
    }

    public function test_admin_can_delete_city(): void
    {
        $country = $this->createCountry();
        $city = $this->createCity($country);

        $response = $this->actingAs($this->adminUser())
            ->deleteJson(
                route(
                    'admin.master-data.cities.destroy',
                    $city
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'City deleted successfully.'
            );

        $this->assertDatabaseMissing('cities', [
            'id' => $city->id,
        ]);
    }

    public function test_missing_city_returns_not_found(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.cities.show',
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
                [
                    'name' => 'Bangladesh',
                    'iso2' => 'BD',
                    'iso3' => 'BGD',
                    'phone_code' => '+880',
                    'is_active' => true,
                ],
                $overrides
            )
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCity(
        Country $country,
        array $overrides = []
    ): City {
        return City::query()->create(
            array_merge(
                $this->validCityPayload($country),
                $overrides
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validCityPayload(
        Country $country
    ): array {
        return [
            'country_id' => $country->id,
            'name' => 'Dhaka',
            'code' => 'DAC',
            'timezone' => 'Asia/Dhaka',
            'is_active' => true,
        ];
    }
}