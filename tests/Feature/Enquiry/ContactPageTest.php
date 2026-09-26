<?php

namespace Tests\Feature\Enquiry;

use App\Models\Enquiry;
use App\Services\SiteContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders_without_fabricated_details(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Get In Touch')
            ->assertSee('Contact Eagle Global Hub | Travel &amp; Visa Support', false)
            ->assertSee('Send us a message using the form and our team will reply to you by email.')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertDontSee('"contactPoint"', false)
            ->assertDontSee('Open in Google Maps')
            ->assertDontSee('+880')
            ->assertDontSee('support@');
    }

    public function test_contact_page_shows_only_admin_filled_details(): void
    {
        app(SiteContentService::class)->saveSection('contact', [
            'phone' => '+880 1700 000000',
            'email' => 'hello@eagle.test',
            'map_url' => 'https://maps.example/eagle',
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('+880 1700 000000')
            ->assertSee('mailto:hello@eagle.test', false)
            ->assertSee('https://maps.example/eagle', false)
            ->assertSee('"contactPoint"', false)
            ->assertDontSee('Office address');
    }

    public function test_contact_message_is_stored_as_contact_enquiry(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'Arif Hossain',
            'email' => 'arif@example.com',
            'phone' => '01711000000',
            'subject' => 'Work visa',
            'message' => 'I would like to know about work permits.',
        ]);

        $response->assertRedirect(route('contact').'#message');

        $enquiry = Enquiry::query()->sole();
        $this->assertSame(Enquiry::SERVICE_CONTACT, $enquiry->service);
        $this->assertSame(['subject' => 'Work visa'], $enquiry->details);
        $this->assertSame('I would like to know about work permits.', $enquiry->message);

        $this->followRedirects($response)
            ->assertSee('data-track-on-load="contact"', false)
            ->assertSee('Thank you, your message has been sent');
    }

    public function test_contact_message_is_validated_and_honeypot_is_silent(): void
    {
        $this->post(route('contact.store'), ['email' => 'nope'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->post(route('contact.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'subject' => 'Offer',
            'message' => 'Buy now',
            'website' => 'spam',
        ])->assertRedirect(route('contact').'#message');

        $this->assertDatabaseCount('enquiries', 0);
    }
}
