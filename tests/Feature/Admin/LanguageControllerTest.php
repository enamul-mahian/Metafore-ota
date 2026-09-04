<?php

namespace Tests\Feature\Admin;

use App\Models\Language;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LanguageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_and_customer_cannot_access_languages(): void
    {
        $this->getJson(route('admin.master-data.languages.index'))
            ->assertUnauthorized();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->getJson(route('admin.master-data.languages.index'))
            ->assertForbidden();
        $this->actingAs($customer)
            ->postJson(route('admin.master-data.languages.store'), $this->payload())
            ->assertForbidden();
        $this->assertDatabaseCount('languages', 0);
    }

    public function test_admin_can_list_languages_in_configured_order(): void
    {
        $this->createLanguage(['name' => 'English', 'code' => 'en', 'sort_order' => 20]);
        $this->createLanguage(['name' => 'Hindi', 'code' => 'hi', 'sort_order' => 10]);
        $this->createLanguage(['name' => 'Bengali', 'code' => 'bn', 'sort_order' => 10]);

        $this->actingAs($this->admin())
            ->getJson(route('admin.master-data.languages.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.code', 'bn')
            ->assertJsonPath('data.1.code', 'hi')
            ->assertJsonPath('data.2.code', 'en');
    }

    public function test_admin_can_create_and_show_language(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.master-data.languages.store'), $this->payload());

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Language created successfully.')
            ->assertJsonPath('data.name', 'Bengali')
            ->assertJsonPath('data.code', 'bn')
            ->assertJsonPath('data.native_name', 'বাংলা')
            ->assertJsonPath('data.sort_order', 10)
            ->assertJsonPath('data.is_active', true);

        $language = Language::query()->firstOrFail();
        $this->actingAs($this->admin())
            ->getJson(route('admin.master-data.languages.show', $language))
            ->assertOk()
            ->assertJsonPath('data.id', $language->id);
    }

    public function test_language_validation_rejects_invalid_values(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.master-data.languages.store'), [
                'name' => '',
                'code' => 'EN',
                'native_name' => str_repeat('x', 151),
                'sort_order' => -1,
                'is_active' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name', 'code', 'native_name', 'sort_order', 'is_active',
            ]);

        $this->assertDatabaseCount('languages', 0);
    }

    public function test_language_name_and_code_must_be_unique(): void
    {
        $this->createLanguage();

        $this->actingAs($this->admin())
            ->postJson(route('admin.master-data.languages.store'), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code']);

        $this->assertDatabaseCount('languages', 1);
    }

    public function test_admin_can_update_language_with_its_existing_unique_values(): void
    {
        $language = $this->createLanguage();

        $this->actingAs($this->admin())
            ->patchJson(
                route('admin.master-data.languages.update', $language),
                $this->payload([
                    'native_name' => null,
                    'sort_order' => 5,
                    'is_active' => false,
                ])
            )
            ->assertOk()
            ->assertJsonPath('message', 'Language updated successfully.')
            ->assertJsonPath('data.native_name', null)
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'name' => 'Bengali',
            'code' => 'bn',
            'native_name' => null,
            'sort_order' => 5,
            'is_active' => false,
        ]);
    }

    public function test_update_rejects_values_used_by_another_language(): void
    {
        $bengali = $this->createLanguage();
        $this->createLanguage(['name' => 'English', 'code' => 'en']);

        $this->actingAs($this->admin())
            ->patchJson(
                route('admin.master-data.languages.update', $bengali),
                $this->payload(['name' => 'English', 'code' => 'en'])
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_read_only_admin_can_view_but_cannot_mutate_languages(): void
    {
        $language = $this->createLanguage();
        $admin = $this->admin();
        Role::findByName('admin')->revokePermissionTo('master-data.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->getJson(route('admin.master-data.languages.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->getJson(route('admin.master-data.languages.show', $language))
            ->assertOk();
        $this->actingAs($admin)
            ->postJson(route('admin.master-data.languages.store'), $this->payload(['code' => 'en']))
            ->assertForbidden();
        $this->actingAs($admin)
            ->patchJson(route('admin.master-data.languages.update', $language), $this->payload())
            ->assertForbidden();
        $this->actingAs($admin)
            ->deleteJson(route('admin.master-data.languages.destroy', $language))
            ->assertForbidden();

        $this->assertDatabaseCount('languages', 1);
    }

    public function test_admin_can_delete_language_and_missing_language_is_not_found(): void
    {
        $language = $this->createLanguage();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->deleteJson(route('admin.master-data.languages.destroy', $language))
            ->assertOk()
            ->assertJsonPath('message', 'Language deleted successfully.');
        $this->assertDatabaseCount('languages', 0);
        $this->actingAs($admin)
            ->getJson(route('admin.master-data.languages.show', 999999))
            ->assertNotFound();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function createLanguage(array $overrides = []): Language
    {
        return Language::query()->create($this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bengali',
            'code' => 'bn',
            'native_name' => 'বাংলা',
            'sort_order' => 10,
            'is_active' => true,
        ], $overrides);
    }
}
