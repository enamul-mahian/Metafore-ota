<?php

namespace Tests\Feature\Enquiry;

use App\Models\Enquiry;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEnquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_and_customer_is_forbidden(): void
    {
        $enquiry = Enquiry::factory()->create();

        $this->get(route('admin.enquiries.index'))->assertRedirect(route('login'));
        $this->get(route('admin.newsletter.index'))->assertRedirect(route('login'));

        $customer = $this->user('customer');

        $this->actingAs($customer)->get(route('admin.enquiries.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.enquiries.show', $enquiry))->assertForbidden();
        $this->actingAs($customer)
            ->patch(route('admin.enquiries.update', $enquiry), ['status' => Enquiry::STATUS_CLOSED])
            ->assertForbidden();
        $this->actingAs($customer)->get(route('admin.newsletter.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.newsletter.export'))->assertForbidden();

        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->fresh()->status);
    }

    public function test_admin_can_list_filter_and_search_enquiries(): void
    {
        Enquiry::factory()->create(['name' => 'Hotel Seeker', 'service' => 'hotels']);
        Enquiry::factory()->contact()->create(['name' => 'Contact Person']);
        Enquiry::factory()->status(Enquiry::STATUS_CLOSED)->create(['name' => 'Closed Lead', 'service' => 'hotels']);

        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.enquiries.index'))
            ->assertOk()
            ->assertSee('Enquiries')
            ->assertSee('Hotel Seeker')
            ->assertSee('Contact Person')
            ->assertSee('2 new');

        $this->actingAs($admin)
            ->get(route('admin.enquiries.index', ['service' => 'contact']))
            ->assertOk()
            ->assertSee('Contact Person')
            ->assertDontSee('Hotel Seeker');

        $this->actingAs($admin)
            ->get(route('admin.enquiries.index', ['status' => 'closed']))
            ->assertSee('Closed Lead')
            ->assertDontSee('Hotel Seeker');

        $this->actingAs($admin)
            ->get(route('admin.enquiries.index', ['search' => 'Seeker']))
            ->assertSee('Hotel Seeker')
            ->assertDontSee('Contact Person');
    }

    public function test_admin_can_view_and_update_enquiry(): void
    {
        $enquiry = Enquiry::factory()->create([
            'service' => 'cabs',
            'details' => ['pickup' => 'Airport', 'dropoff' => 'Gulshan', 'passengers' => 2],
        ]);

        $admin = $this->user('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Pick-up location')
            ->assertSee('Airport')
            ->assertSee('Drop-off location')
            ->assertSee('Save Changes');

        $this->actingAs($admin)
            ->patch(route('admin.enquiries.update', $enquiry), [
                'status' => Enquiry::STATUS_CONTACTED,
                'admin_note' => 'Called the customer.',
            ])
            ->assertRedirect(route('admin.enquiries.show', $enquiry))
            ->assertSessionHas('status');

        $enquiry->refresh();
        $this->assertSame(Enquiry::STATUS_CONTACTED, $enquiry->status);
        $this->assertSame('Called the customer.', $enquiry->admin_note);

        $this->actingAs($admin)
            ->patch(route('admin.enquiries.update', $enquiry), ['status' => 'archived'])
            ->assertSessionHasErrors(['status']);
    }

    public function test_admin_can_list_and_export_newsletter_subscribers(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'first@example.com']);
        NewsletterSubscriber::factory()->create(['email' => '=cmd@example.com']);
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.com']);

        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.newsletter.index'))
            ->assertOk()
            ->assertSee('first@example.com')
            ->assertSee('2 subscribed')
            ->assertSee('Export CSV');

        $response = $this->actingAs($admin)->get(route('admin.newsletter.export'));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();

        $this->assertStringContainsString("email,status,source,subscribed_at\n", $csv);
        $this->assertStringContainsString('first@example.com,subscribed', $csv);
        $this->assertStringContainsString("'=cmd@example.com", $csv);
        $this->assertStringNotContainsString('gone@example.com', $csv);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }
}
