<?php

namespace Tests\Feature\Enquiry;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe_and_sees_tracked_confirmation(): void
    {
        $response = $this->from(route('packages.index'))
            ->post(route('newsletter.store'), ['email' => ' Reader@Example.com ', 'source' => 'packages']);

        $response->assertRedirect(route('packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'reader@example.com',
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'source' => 'packages',
        ]);

        $this->followRedirects($response)
            ->assertSee('data-track-on-load="subscribe"', false)
            ->assertSee('Thank you for subscribing');
    }

    public function test_duplicate_email_is_a_friendly_success(): void
    {
        NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'reader@example.com', 'source' => 'footer']);

        $this->from(route('packages.index'))
            ->post(route('newsletter.store'), ['email' => 'reader@example.com', 'source' => 'home'])
            ->assertRedirect(route('packages.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('newsletter_subscribed');

        $this->postJson(route('newsletter.store'), ['email' => 'reader@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Thank you for subscribing. We will email you travel updates and offers.');

        $subscriber = NewsletterSubscriber::query()->sole();
        $this->assertSame(NewsletterSubscriber::STATUS_SUBSCRIBED, $subscriber->status);
        $this->assertSame('footer', $subscriber->source);
    }

    public function test_invalid_email_and_honeypot_are_handled(): void
    {
        $this->post(route('newsletter.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrorsIn('newsletter', ['email']);

        $this->post(route('newsletter.store'), ['email' => 'bot@example.com', 'website' => 'spam'])
            ->assertSessionHas('newsletter_subscribed');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
