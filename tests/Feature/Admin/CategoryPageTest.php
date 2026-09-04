<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_categories_page(): void
    {
        $this->get(route('admin.categories.manage'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_view_categories_page(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('admin.categories.manage'))
            ->assertForbidden();
    }

    public function test_admin_can_view_categories_page_with_management_controls(): void
    {
        Category::query()->create([
            'name' => 'Flights',
            'slug' => 'flights',
            'description' => 'Flight services',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.manage'));

        $response
            ->assertOk()
            ->assertSee('Categories')
            ->assertSee('Add Category')
            ->assertSee('category-form', false)
            ->assertSee('class="cat-edit"', false)
            ->assertSee('class="cat-delete"', false);
    }

    public function test_super_admin_can_view_categories_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.categories.manage'))
            ->assertOk()
            ->assertSee('Add Category');
    }

    public function test_categories_are_rendered_in_sort_order_then_name(): void
    {
        Category::query()->create([
            'name' => 'Visa',
            'slug' => 'visa',
            'description' => 'Visa services',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        Category::query()->create([
            'name' => 'Tours',
            'slug' => 'tours',
            'description' => 'Tour services',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Category::query()->create([
            'name' => 'Flights',
            'slug' => 'flights',
            'description' => 'Flight services',
            'sort_order' => 10,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.manage'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Flights',
                'Tours',
                'Visa',
            ])
            ->assertSee('Inactive')
            ->assertSee('Active');
    }

    public function test_admin_without_manage_permission_gets_read_only_categories_page(): void
    {
        $admin = $this->adminUser();
        Role::findByName('admin')->revokePermissionTo('master-data.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($admin)
            ->get(route('admin.categories.manage'));

        $response
            ->assertOk()
            ->assertSee('Categories')
            ->assertDontSee('Add Category')
            ->assertDontSee('category-form', false)
            ->assertDontSee('class="cat-edit"', false)
            ->assertDontSee('class="cat-delete"', false);
    }

    public function test_admin_sidebar_contains_categories_link(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.categories.manage'))
            ->assertOk()
            ->assertSee(route('admin.categories.manage'), false)
            ->assertSee('Categories');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();

        $user->assignRole('admin');

        return $user;
    }
}
