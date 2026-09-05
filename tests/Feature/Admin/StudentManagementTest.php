<?php

namespace Tests\Feature\Admin;

use App\Models\Country;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_cannot_access_any_student_route(): void
    {
        $this->get(route('admin.students.index'))->assertRedirect(route('login'));
        $customer = $this->user('customer');
        $student = $this->student();
        $this->actingAs($customer)->get(route('admin.students.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.students.show', $student))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.students.store'), $this->payload())->assertForbidden();
        $this->actingAs($customer)->patch(route('admin.students.update', $student), $this->payload())->assertForbidden();
        $this->actingAs($customer)->delete(route('admin.students.destroy', $student))->assertForbidden();
    }

    public function test_admin_and_super_admin_can_view_students(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.students.index'))
            ->assertOk()->assertSee('Students')->assertSee('Create Student');
        $this->actingAs($this->user('super-admin'))->get(route('admin.students.index'))->assertOk();
    }

    public function test_admin_can_create_normalized_student_with_country(): void
    {
        $country = $this->country();
        $this->actingAs($this->user('admin'))->post(route('admin.students.store'), $this->payload([
            'first_name' => '  Nusrat ', 'last_name' => ' Jahan  ',
            'email' => ' STUDENT@EXAMPLE.COM ', 'reference_code' => ' stu-1001 ',
            'country_id' => $country->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('students', [
            'first_name' => 'Nusrat', 'last_name' => 'Jahan',
            'email' => 'student@example.com', 'reference_code' => 'STU-1001',
            'country_id' => $country->id,
            'status' => Student::STATUS_ACTIVE,
        ]);
        $this->assertSame(
            '2002-05-10',
            Student::query()->firstOrFail()->date_of_birth?->format('Y-m-d')
        );
    }

    public function test_validation_rejects_malformed_and_duplicate_private_data(): void
    {
        $this->actingAs($this->user('admin'))->post(route('admin.students.store'), [
            'first_name' => '', 'last_name' => '', 'email' => 'bad', 'phone' => 'abc',
            'country_id' => 999999, 'date_of_birth' => now()->addDay()->format('Y-m-d'),
            'reference_code' => 'bad code', 'status' => 'pending', 'notes' => str_repeat('x', 5001),
        ])->assertSessionHasErrors([
            'first_name', 'last_name', 'email', 'phone', 'country_id',
            'date_of_birth', 'reference_code', 'status', 'notes',
        ]);

        $this->student();
        $this->actingAs($this->user('admin'))->post(route('admin.students.store'), $this->payload())
            ->assertSessionHasErrors(['email', 'reference_code']);
        $this->assertDatabaseCount('students', 1);
    }

    public function test_admin_can_show_update_and_delete_student(): void
    {
        $student = $this->student();
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('admin.students.show', $student))
            ->assertOk()->assertSee('Nusrat Jahan')->assertSee('STU-1001')->assertSee('student@example.com');
        $this->actingAs($admin)->patch(route('admin.students.update', $student), $this->payload([
            'last_name' => 'Ahmed', 'status' => Student::STATUS_ARCHIVED, 'notes' => '',
        ]))->assertRedirect(route('admin.students.show', $student));
        $this->assertDatabaseHas('students', [
            'id' => $student->id, 'last_name' => 'Ahmed', 'reference_code' => 'STU-1001',
            'status' => Student::STATUS_ARCHIVED, 'notes' => null,
        ]);
        $this->actingAs($admin)->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_update_rejects_another_students_email_and_reference(): void
    {
        $student = $this->student();
        $this->student(['email' => 'other@example.com', 'reference_code' => 'STU-2002']);
        $this->actingAs($this->user('admin'))->patch(route('admin.students.update', $student), $this->payload([
            'email' => 'other@example.com', 'reference_code' => 'STU-2002',
        ]))->assertSessionHasErrors(['email', 'reference_code']);
    }

    public function test_read_only_admin_can_list_and_show_but_cannot_mutate(): void
    {
        $student = $this->student();
        $admin = $this->user('admin');
        Role::findByName('admin')->revokePermissionTo('students.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($admin)->get(route('admin.students.index'))->assertOk()->assertDontSee('Create Student');
        $this->actingAs($admin)->get(route('admin.students.show', $student))->assertOk()->assertDontSee('Delete Student');
        $this->actingAs($admin)->get(route('admin.students.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.students.store'), $this->payload())->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.students.update', $student), $this->payload())->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.students.destroy', $student))->assertForbidden();
    }

    public function test_search_status_deterministic_order_pagination_navigation_and_permissions(): void
    {
        $older = $this->student(['first_name' => 'Older', 'email' => 'older@example.com', 'reference_code' => 'OLD-1']);
        $newer = $this->student(['first_name' => 'Target', 'email' => 'target@example.com', 'reference_code' => 'TARGET-1', 'status' => Student::STATUS_ARCHIVED]);
        $admin = $this->user('admin');
        $customer = $this->user('customer');
        $this->actingAs($admin)->get(route('admin.students.index', ['search' => 'Target', 'status' => 'archived']))
            ->assertOk()->assertSee('TARGET-1')->assertDontSee('OLD-1');
        $this->actingAs($admin)->get(route('admin.students.index'))->assertOk()
            ->assertSeeInOrder([$newer->reference_code, $older->reference_code]);
        foreach (range(1, 14) as $number) {
            $this->student(['email' => 'page'.$number.'@example.com', 'reference_code' => 'PAGE-'.$number]);
        }
        $this->actingAs($admin)->get(route('admin.students.index'))->assertOk()
            ->assertSee('page=2', false)->assertSee(route('admin.students.index'), false);
        $this->actingAs($admin)->get(route('admin.settings.manage'))->assertOk()->assertSee(route('admin.students.index'), false);
        $this->assertTrue($admin->can('students.view'));
        $this->assertTrue($admin->can('students.manage'));
        $this->assertFalse($customer->can('students.view'));
    }

    public function test_schema_contains_no_speculative_workflow_financial_or_document_fields(): void
    {
        foreach (['tuition_balance', 'payment_balance', 'commission', 'institution_id', 'agent_id', 'visa_status', 'admission_status', 'passport_number', 'document_path'] as $column) {
            $this->assertFalse(Schema::hasColumn('students', $column));
        }
    }

    public function test_missing_student_returns_not_found(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.students.show', 999999))->assertNotFound();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function country(): Country
    {
        return Country::query()->create(['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '+880', 'is_active' => true]);
    }

    /** @param array<string, mixed> $overrides */
    private function student(array $overrides = []): Student
    {
        return Student::query()->create($this->payload($overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Nusrat', 'last_name' => 'Jahan', 'email' => 'student@example.com',
            'phone' => '+880 1712-345678', 'country_id' => null, 'date_of_birth' => '2002-05-10',
            'reference_code' => 'STU-1001', 'status' => Student::STATUS_ACTIVE,
            'notes' => 'Private operational note.',
        ], $overrides);
    }
}
