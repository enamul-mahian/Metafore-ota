<?php

namespace Tests\Feature\WorkVisa;

use App\Enums\WorkVisaApplicationStatus;
use App\Models\Country;
use App\Models\User;
use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDestination;
use App\Notifications\WorkVisaApplicationStatusUpdated;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkVisaAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $application = WorkVisaApplication::factory()->create();
        $destination = $application->destination;

        $this->get(route('admin.work-visa.applications.index'))->assertRedirect(route('login'));

        $customer = $this->user('customer');

        $this->actingAs($customer)->get(route('admin.work-visa.applications.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.work-visa.applications.show', $application))->assertForbidden();
        $this->actingAs($customer)
            ->patch(route('admin.work-visa.applications.status', $application), ['status' => 'approved'])
            ->assertForbidden();
        $this->actingAs($customer)->get(route('admin.work-visa.destinations.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.work-visa.destinations.store'), $this->destinationPayload())->assertForbidden();
        $this->actingAs($customer)->delete(route('admin.work-visa.destinations.destroy', $destination))->assertForbidden();

        $this->assertSame(WorkVisaApplicationStatus::Submitted, $application->fresh()->status);
        $this->assertDatabaseCount('work_visa_destinations', 1);
    }

    public function test_admin_can_filter_and_view_applications(): void
    {
        $canada = WorkVisaDestination::factory()->create();
        $japan = WorkVisaDestination::factory()->create();
        $match = WorkVisaApplication::factory()->for($canada, 'destination')->create(['first_name' => 'Karima', 'passport_number' => 'B99887766']);
        $other = WorkVisaApplication::factory()->for($japan, 'destination')->status(WorkVisaApplicationStatus::Approved)->create(['first_name' => 'Jamal']);
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.work-visa.applications.index', ['search' => 'B9988']))
            ->assertOk()
            ->assertSee($match->reference)
            ->assertDontSee($other->reference);

        $this->actingAs($admin)
            ->get(route('admin.work-visa.applications.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee($other->reference)
            ->assertDontSee($match->reference);

        $this->actingAs($admin)
            ->get(route('admin.work-visa.applications.index', ['destination' => $canada->id]))
            ->assertOk()
            ->assertSee($match->reference)
            ->assertDontSee($other->reference);

        $this->actingAs($admin)
            ->get(route('admin.work-visa.applications.show', $match))
            ->assertOk()
            ->assertSee('B99887766')
            ->assertSee('Update status');
    }

    public function test_admin_can_update_status_with_note_and_notify_applicant(): void
    {
        Notification::fake();
        $application = WorkVisaApplication::factory()->create();
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->patch(route('admin.work-visa.applications.status', $application), [
                'status' => 'documents_review',
                'note' => 'Please upload a clearer passport scan.',
                'notify_applicant' => '1',
            ])
            ->assertRedirect(route('admin.work-visa.applications.show', $application))
            ->assertSessionHas('status');

        $application->refresh();
        $this->assertSame(WorkVisaApplicationStatus::DocumentsReview, $application->status);
        $this->assertSame('Please upload a clearer passport scan.', $application->admin_note);
        $this->assertDatabaseHas('work_visa_application_events', [
            'work_visa_application_id' => $application->id,
            'status' => 'documents_review',
            'note' => 'Please upload a clearer passport scan.',
            'user_id' => $admin->id,
        ]);
        Notification::assertSentTo($application->user, WorkVisaApplicationStatusUpdated::class);

        $this->actingAs($application->user)
            ->get(route('work-visa.applications.show', $application))
            ->assertOk()
            ->assertSee('Please upload a clearer passport scan.')
            ->assertSee('Documents review');
    }

    public function test_status_update_is_validated_and_not_notified_by_default(): void
    {
        Notification::fake();
        $application = WorkVisaApplication::factory()->create();
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->patch(route('admin.work-visa.applications.status', $application), ['status' => 'teleported'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->patch(route('admin.work-visa.applications.status', $application), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame(WorkVisaApplicationStatus::Approved, $application->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_admin_can_manage_destinations(): void
    {
        $admin = $this->user('admin');
        $country = Country::query()->create(['name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.work-visa.destinations.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.work-visa.destinations.store'), $this->destinationPayload([
                'country_id' => $country->id,
                'slug' => ' Canada Skilled ',
                'job_categories' => "Nurse\n\n Welder \n",
                'requirements' => "Passport\nIELTS certificate",
                'service_fee' => '',
            ]))
            ->assertRedirect(route('admin.work-visa.destinations.index'));

        $destination = WorkVisaDestination::query()->sole();
        $this->assertSame('canada-skilled', $destination->slug);
        $this->assertSame(['Nurse', 'Welder'], $destination->job_categories);
        $this->assertSame(['Passport', 'IELTS certificate'], $destination->requirements);
        $this->assertNull($destination->service_fee);
        $this->assertTrue($destination->is_active);

        $this->actingAs($admin)
            ->get(route('admin.work-visa.destinations.index'))
            ->assertOk()
            ->assertSee('Canada')
            ->assertSee('Not set');

        $this->actingAs($admin)
            ->get(route('admin.work-visa.destinations.edit', $destination))
            ->assertOk()
            ->assertSee("Nurse\nWelder", false);

        $this->actingAs($admin)
            ->patch(route('admin.work-visa.destinations.update', $destination), $this->destinationPayload([
                'country_id' => $country->id,
                'slug' => 'canada',
                'service_fee' => '75000',
                'is_active' => '0',
            ]))
            ->assertRedirect(route('admin.work-visa.destinations.index'));

        $destination->refresh();
        $this->assertSame('75000.00', $destination->service_fee);
        $this->assertFalse($destination->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.work-visa.destinations.destroy', $destination))
            ->assertRedirect(route('admin.work-visa.destinations.index'));

        $this->assertDatabaseCount('work_visa_destinations', 0);
    }

    public function test_destination_validation_and_delete_protection(): void
    {
        $admin = $this->user('admin');
        $existing = WorkVisaDestination::factory()->create(['slug' => 'uae']);
        WorkVisaApplication::factory()->for($existing, 'destination')->create();

        $this->actingAs($admin)
            ->post(route('admin.work-visa.destinations.store'), $this->destinationPayload([
                'country_id' => 999999,
                'slug' => 'uae',
                'service_fee' => '-5',
                'currency' => 'TAKA',
            ]))
            ->assertSessionHasErrors(['country_id', 'slug', 'service_fee', 'currency']);

        $this->actingAs($admin)
            ->delete(route('admin.work-visa.destinations.destroy', $existing))
            ->assertRedirect(route('admin.work-visa.destinations.edit', $existing))
            ->assertSessionHasErrors('destination');

        $this->assertDatabaseCount('work_visa_destinations', 1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function destinationPayload(array $overrides = []): array
    {
        return [
            'country_id' => null,
            'slug' => 'destination',
            'visa_type' => 'Skilled Worker Visa',
            'summary' => 'Skilled worker route.',
            'processing_time' => '8–12 weeks',
            'service_fee' => '',
            'currency' => 'BDT',
            'job_categories' => '',
            'requirements' => '',
            'is_active' => '1',
            'sort_order' => '0',
            ...$overrides,
        ];
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
