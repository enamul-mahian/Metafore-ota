<?php

namespace Tests\Feature\WorkVisa;

use App\Enums\WorkVisaApplicationStatus;
use App\Models\User;
use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDestination;
use App\Models\WorkVisaDocument;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkVisaApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_apply_requires_authentication_verification_and_permission(): void
    {
        $this->get(route('work-visa.apply'))->assertRedirect(route('login'));
        $this->post(route('work-visa.store'))->assertRedirect(route('login'));

        $unverified = User::factory()->unverified()->create();
        $unverified->assignRole('customer');
        $this->actingAs($unverified)->get(route('work-visa.apply'))->assertRedirect(route('verification.notice'));

        $withoutPermission = User::factory()->create();
        $this->actingAs($withoutPermission)->get(route('work-visa.apply'))->assertForbidden();
        $this->actingAs($withoutPermission)->post(route('work-visa.store'), $this->payload())->assertForbidden();

        $this->assertDatabaseCount('work_visa_applications', 0);
    }

    public function test_apply_form_prefills_user_and_preselects_destination(): void
    {
        $destination = WorkVisaDestination::factory()->create(['slug' => 'canada', 'processing_time' => '8–12 weeks']);
        $customer = $this->customer(['name' => 'Rahim Uddin', 'email' => 'rahim@example.com']);

        $this->actingAs($customer)
            ->get(route('work-visa.apply', ['destination' => 'canada']))
            ->assertOk()
            ->assertSee('Application Summary')
            ->assertSee('value="Rahim"', false)
            ->assertSee('value="Uddin"', false)
            ->assertSee('value="rahim@example.com"', false)
            ->assertSee('value="'.$destination->id.'" selected', false)
            ->assertSee('8–12 weeks')
            ->assertSee('noindex,nofollow', false);
    }

    public function test_customer_can_submit_application_with_documents(): void
    {
        $destination = WorkVisaDestination::factory()->create(['job_categories' => ['Construction']]);
        $customer = $this->customer();

        $response = $this->actingAs($customer)->post(route('work-visa.store'), $this->payload([
            'work_visa_destination_id' => $destination->id,
            'passport_number' => ' a0123 4567 ',
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'certificates' => [
                UploadedFile::fake()->create('degree.pdf', 200, 'application/pdf'),
                UploadedFile::fake()->create('experience.pdf', 200, 'application/pdf'),
            ],
        ]));

        $application = WorkVisaApplication::query()->sole();

        $response->assertRedirect(route('work-visa.applications.show', $application));
        $this->assertMatchesRegularExpression('/^EGH-WV-\d{4}-[A-Z0-9]{6}$/', $application->reference);
        $this->assertSame($customer->id, $application->user_id);
        $this->assertSame('A01234567', $application->passport_number);
        $this->assertSame(WorkVisaApplicationStatus::Submitted, $application->status);
        $this->assertCount(4, $application->documents);
        $this->assertSame(['certificate', 'certificate', 'passport', 'photo'], $application->documents->pluck('type')->sort()->values()->all());
        $this->assertDatabaseHas('work_visa_application_events', [
            'work_visa_application_id' => $application->id,
            'status' => 'submitted',
        ]);

        foreach ($application->documents as $document) {
            $this->assertStringStartsWith('work-visa/'.$application->id.'/', $document->path);
            Storage::disk('local')->assertExists($document->path);
        }

        $this->actingAs($customer)
            ->withSession(['work_visa_submitted' => true])
            ->get(route('work-visa.applications.show', $application))
            ->assertOk()
            ->assertSee('data-track-on-load="lead"', false)
            ->assertSee($application->reference);
    }

    public function test_submission_requires_passport_document(): void
    {
        $destination = WorkVisaDestination::factory()->create();

        $this->actingAs($this->customer())
            ->post(route('work-visa.store'), $this->payload([
                'work_visa_destination_id' => $destination->id,
                'passport' => null,
            ]))
            ->assertSessionHasErrors('passport');

        $this->assertDatabaseCount('work_visa_applications', 0);
    }

    public function test_submission_rejects_bad_mime_and_oversized_files(): void
    {
        $destination = WorkVisaDestination::factory()->create();
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('work-visa.store'), $this->payload([
                'work_visa_destination_id' => $destination->id,
                'passport' => UploadedFile::fake()->create('passport.exe', 100, 'application/x-msdownload'),
            ]))
            ->assertSessionHasErrors('passport');

        $this->actingAs($customer)
            ->post(route('work-visa.store'), $this->payload([
                'work_visa_destination_id' => $destination->id,
                'cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('cv');

        $this->assertDatabaseCount('work_visa_applications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_submission_validates_destination_and_job_category(): void
    {
        $inactive = WorkVisaDestination::factory()->inactive()->create();
        $active = WorkVisaDestination::factory()->create(['job_categories' => ['Construction']]);
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post(route('work-visa.store'), $this->payload(['work_visa_destination_id' => $inactive->id]))
            ->assertSessionHasErrors('work_visa_destination_id');

        $this->actingAs($customer)
            ->post(route('work-visa.store'), $this->payload([
                'work_visa_destination_id' => $active->id,
                'job_category' => 'Astronaut',
            ]))
            ->assertSessionHasErrors('job_category');

        $this->actingAs($customer)
            ->post(route('work-visa.store'), $this->payload([
                'work_visa_destination_id' => $active->id,
                'date_of_birth' => now()->subYears(16)->toDateString(),
                'confirm' => null,
            ]))
            ->assertSessionHasErrors(['date_of_birth', 'confirm']);

        $this->assertDatabaseCount('work_visa_applications', 0);
    }

    public function test_customer_sees_only_own_applications(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $mine = WorkVisaApplication::factory()->for($owner)->create();
        $theirs = WorkVisaApplication::factory()->for($other)->create();

        $this->actingAs($owner)
            ->get(route('work-visa.applications.index'))
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($theirs->reference);

        $this->actingAs($owner)->get(route('work-visa.applications.show', $mine))->assertOk()->assertSee($mine->passport_number);
        $this->actingAs($owner)->get(route('work-visa.applications.show', $theirs))->assertNotFound();
    }

    public function test_document_download_is_limited_to_owner_and_admin_staff(): void
    {
        $owner = $this->customer();
        $application = WorkVisaApplication::factory()->for($owner)->create();
        Storage::disk('local')->put('work-visa/'.$application->id.'/passport.pdf', '%PDF-1.4 test');
        $document = WorkVisaDocument::factory()->for($application, 'application')->create([
            'path' => 'work-visa/'.$application->id.'/passport.pdf',
            'original_name' => 'my-passport.pdf',
        ]);

        $this->get(route('work-visa.documents.download', $document))->assertRedirect(route('login'));

        $this->actingAs($owner)
            ->get(route('work-visa.documents.download', $document))
            ->assertOk()
            ->assertDownload('my-passport.pdf');

        $this->actingAs($this->customer())
            ->get(route('work-visa.documents.download', $document))
            ->assertNotFound();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->get(route('work-visa.documents.download', $document))
            ->assertOk();
    }

    public function test_owner_can_add_documents_only_while_review_is_open(): void
    {
        $owner = $this->customer();
        $application = WorkVisaApplication::factory()->for($owner)->status(WorkVisaApplicationStatus::DocumentsReview)->create();

        $this->actingAs($owner)
            ->post(route('work-visa.applications.documents.store', $application), [
                'type' => 'certificate',
                'document' => UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('work-visa.applications.show', $application));

        $this->assertSame(1, $application->documents()->count());

        $this->actingAs($this->customer())
            ->post(route('work-visa.applications.documents.store', $application), [
                'type' => 'other',
                'document' => UploadedFile::fake()->create('intruder.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();

        $application->update(['status' => WorkVisaApplicationStatus::Approved]);

        $this->actingAs($owner)
            ->post(route('work-visa.applications.documents.store', $application), [
                'type' => 'other',
                'document' => UploadedFile::fake()->create('late.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('document');

        $this->assertSame(1, $application->documents()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function customer(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('customer');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_filter([
            'work_visa_destination_id' => null,
            'job_category' => 'Construction',
            'title' => 'Mr.',
            'first_name' => 'Rahim',
            'last_name' => 'Uddin',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'gender' => 'male',
            'nationality' => 'Bangladeshi',
            'passport_number' => 'A01234567',
            'passport_expiry' => now()->addYears(4)->toDateString(),
            'phone' => '+880 1711 000000',
            'email' => 'rahim@example.com',
            'current_address' => 'House 1, Road 2, Dhaka',
            'experience_years' => '4',
            'confirm' => '1',
            'passport' => UploadedFile::fake()->create('passport.pdf', 300, 'application/pdf'),
            ...$overrides,
        ], fn (mixed $value): bool => $value !== null);
    }
}
