<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_category_index(): void
    {
        $this->getJson(
            route('admin.master-data.categories.index')
        )->assertUnauthorized();
    }

    public function test_customer_cannot_access_category_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->getJson(route('admin.master-data.categories.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_create_category(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->postJson(
                route('admin.master-data.categories.store'),
                $this->validCategoryPayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_can_list_categories_sorted_by_sort_order_then_name(): void
    {
        $this->createCategory([
            'name' => 'Visa',
            'slug' => 'visa',
            'sort_order' => 20,
        ]);

        $this->createCategory([
            'name' => 'Tours',
            'slug' => 'tours',
            'sort_order' => 10,
        ]);

        $this->createCategory([
            'name' => 'Flights',
            'slug' => 'flights',
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('admin.master-data.categories.index'));

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Flights')
            ->assertJsonPath('data.1.name', 'Tours')
            ->assertJsonPath('data.2.name', 'Visa');
    }

    public function test_admin_can_create_category(): void
    {
        $payload = $this->validCategoryPayload();

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.categories.store'),
                $payload
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Category created successfully.'
            )
            ->assertJsonPath('data.name', 'Flights')
            ->assertJsonPath('data.slug', 'flights')
            ->assertJsonPath('data.description', 'Flight products')
            ->assertJsonPath('data.sort_order', 10)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Flights',
            'slug' => 'flights',
            'description' => 'Flight products',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_category_creation_validates_required_and_formatted_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.categories.store'),
                [
                    'name' => '',
                    'slug' => 'Invalid Slug',
                    'description' => str_repeat('x', 5001),
                    'sort_order' => -1,
                    'is_active' => 'active',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'slug',
                'description',
                'sort_order',
                'is_active',
            ]);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_creation_rejects_duplicate_name_and_slug(): void
    {
        $this->createCategory();

        $response = $this->actingAs($this->adminUser())
            ->postJson(
                route('admin.master-data.categories.store'),
                [
                    'name' => 'Flights',
                    'slug' => 'flights',
                    'description' => 'Duplicate',
                    'sort_order' => 20,
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'slug',
            ]);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_admin_can_show_category(): void
    {
        $category = $this->createCategory();

        $response = $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.categories.show',
                    $category
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Flights')
            ->assertJsonPath('data.slug', 'flights');
    }

    public function test_admin_can_update_category_and_keep_its_own_unique_values(): void
    {
        $category = $this->createCategory();

        $response = $this->actingAs($this->adminUser())
            ->patchJson(
                route(
                    'admin.master-data.categories.update',
                    $category
                ),
                [
                    'name' => 'Flights',
                    'slug' => 'flights',
                    'description' => 'Updated flight products',
                    'sort_order' => 5,
                    'is_active' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Category updated successfully.'
            )
            ->assertJsonPath('data.name', 'Flights')
            ->assertJsonPath('data.slug', 'flights')
            ->assertJsonPath('data.description', 'Updated flight products')
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Flights',
            'slug' => 'flights',
            'description' => 'Updated flight products',
            'sort_order' => 5,
            'is_active' => false,
        ]);
    }

    public function test_category_update_rejects_name_and_slug_used_by_another_category(): void
    {
        $flights = $this->createCategory();

        $this->createCategory([
            'name' => 'Hotels',
            'slug' => 'hotels',
            'description' => 'Hotel products',
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->patchJson(
                route(
                    'admin.master-data.categories.update',
                    $flights
                ),
                [
                    'name' => 'Hotels',
                    'slug' => 'hotels',
                    'description' => 'Conflict',
                    'sort_order' => 30,
                    'is_active' => true,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'slug',
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $flights->id,
            'name' => 'Flights',
            'slug' => 'flights',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = $this->createCategory();

        $response = $this->actingAs($this->adminUser())
            ->deleteJson(
                route(
                    'admin.master-data.categories.destroy',
                    $category
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Category deleted successfully.'
            );

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_missing_category_returns_not_found(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(
                route(
                    'admin.master-data.categories.show',
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
    private function createCategory(
        array $overrides = []
    ): Category {
        return Category::query()->create(
            array_merge(
                $this->validCategoryPayload(),
                $overrides
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validCategoryPayload(): array
    {
        return [
            'name' => 'Flights',
            'slug' => 'flights',
            'description' => 'Flight products',
            'sort_order' => 10,
            'is_active' => true,
        ];
    }
}
