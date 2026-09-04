<?php

namespace Tests\Feature\Admin;

use App\Models\Language;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LanguagePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $this->get(route('admin.languages.manage'))
            ->assertRedirect(route('login'));

        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer)
            ->get(route('admin.languages.manage'))
            ->assertForbidden();
    }

    public function test_admin_and_super_admin_can_manage_languages(): void
    {
        $this->createLanguage();

        $this->actingAs($this->user('admin'))
            ->get(route('admin.languages.manage'))
            ->assertOk()
            ->assertSee('Add Language')
            ->assertSee('language-form', false)
            ->assertSee('class="lng-edit"', false)
            ->assertSee('class="lng-delete"', false)
            ->assertSee('Bengali')
            ->assertSee('বাংলা');

        $this->actingAs($this->user('super-admin'))
            ->get(route('admin.languages.manage'))
            ->assertOk()
            ->assertSee('Add Language');
    }

    public function test_languages_render_in_sort_order_then_name(): void
    {
        $this->createLanguage(['name' => 'English', 'code' => 'en', 'sort_order' => 20]);
        $this->createLanguage(['name' => 'Hindi', 'code' => 'hi', 'sort_order' => 10]);
        $this->createLanguage(['name' => 'Bengali', 'code' => 'bn', 'sort_order' => 10, 'is_active' => false]);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.languages.manage'))
            ->assertOk()
            ->assertSeeInOrder(['Bengali', 'Hindi', 'English'])
            ->assertSee('Inactive')
            ->assertSee('Active');
    }

    public function test_read_only_admin_sees_no_management_controls(): void
    {
        $admin = $this->user('admin');
        Role::findByName('admin')->revokePermissionTo('master-data.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->get(route('admin.languages.manage'))
            ->assertOk()
            ->assertDontSee('Add Language')
            ->assertDontSee('language-form', false)
            ->assertDontSee('class="lng-edit"', false)
            ->assertDontSee('class="lng-delete"', false);
    }

    public function test_both_admin_sidebars_link_to_languages(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.languages.manage'))
            ->assertOk()
            ->assertSee(route('admin.languages.manage'), false);
        $this->actingAs($admin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee(route('admin.languages.manage'), false);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function createLanguage(array $overrides = []): Language
    {
        return Language::query()->create(array_merge([
            'name' => 'Bengali',
            'code' => 'bn',
            'native_name' => 'বাংলা',
            'sort_order' => 10,
            'is_active' => true,
        ], $overrides));
    }
}
