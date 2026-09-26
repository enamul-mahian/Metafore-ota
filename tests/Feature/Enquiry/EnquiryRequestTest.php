<?php

namespace Tests\Feature\Enquiry;

use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnquiryRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function services(): array
    {
        return collect(['flights', 'hotels', 'tours', 'visa', 'packages', 'trains', 'buses', 'cabs', 'insurance', 'other'])
            ->mapWithKeys(fn (string $service): array => [$service => [$service]])
            ->all();
    }

    #[DataProvider('services')]
    public function test_guest_can_open_request_form_for_service(string $service): void
    {
        $this->get(route('enquiries.create', ['service' => $service]))
            ->assertOk()
            ->assertSee(config('enquiries.services.'.$service.'.title'))
            ->assertSee('What happens next')
            ->assertSee('name="website"', false)
            ->assertDontSee('data-track-on-load', false);
    }

    #[DataProvider('services')]
    public function test_guest_can_submit_request_for_service(string $service): void
    {
        $response = $this->post(route('enquiries.store', ['service' => $service]), [
            ...$this->contactDetails(),
            ...$this->validDetails($service),
        ]);

        $response->assertRedirect(route('enquiries.create', ['service' => $service]));

        $enquiry = Enquiry::query()->sole();
        $this->assertSame($service, $enquiry->service);
        $this->assertSame('Nadia Rahman', $enquiry->name);
        $this->assertSame('nadia@example.com', $enquiry->email);
        $this->assertSame(Enquiry::STATUS_NEW, $enquiry->status);
        $this->assertNull($enquiry->user_id);
        $this->assertEqualsCanonicalizing(array_keys($this->validDetails($service)), array_keys($enquiry->details));

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('data-track-on-load="lead"', false)
            ->assertSee('Thank you, your request has been sent')
            ->assertSee('Request reference: #'.$enquiry->id);
    }

    public function test_unknown_service_returns_not_found(): void
    {
        $this->get('/request/spaceships')->assertNotFound();
        $this->get('/request/contact')->assertNotFound();
        $this->post('/request/spaceships', $this->contactDetails())->assertNotFound();

        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_request_is_validated_with_service_specific_rules(): void
    {
        $this->from(route('enquiries.create', ['service' => 'hotels']))
            ->post(route('enquiries.store', ['service' => 'hotels']), [
                'name' => '',
                'email' => 'not-an-email',
                'phone' => 'call me',
                'city' => '',
                'check_in' => now()->subDay()->toDateString(),
                'check_out' => now()->subDays(2)->toDateString(),
                'rooms' => 0,
                'guests' => 50,
            ])
            ->assertRedirect(route('enquiries.create', ['service' => 'hotels']))
            ->assertSessionHasErrors(['name', 'email', 'phone', 'city', 'check_in', 'check_out', 'rooms', 'guests']);

        $this->post(route('enquiries.store', ['service' => 'visa']), [
            ...$this->contactDetails(),
            'nationality' => 'Bangladesh',
            'destination' => 'Japan',
            'visa_type' => 'Diplomatic',
        ])->assertSessionHasErrors(['visa_type']);

        $this->post(route('enquiries.store', ['service' => 'packages']), [
            ...$this->contactDetails(),
            'destination' => 'Maldives',
            'travellers' => 2,
            'travel_month' => now()->subYear()->format('Y-m'),
        ])->assertSessionHasErrors(['travel_month']);

        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_filled_honeypot_is_accepted_silently_without_storing(): void
    {
        $this->post(route('enquiries.store', ['service' => 'tours']), [
            ...$this->contactDetails(),
            ...$this->validDetails('tours'),
            'website' => 'https://spam.example',
        ])->assertRedirect(route('enquiries.create', ['service' => 'tours']));

        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_form_is_prefilled_from_query_string(): void
    {
        $this->get(route('enquiries.create', [
            'service' => 'hotels',
            'city' => 'Cox\'s Bazar',
            'rooms' => '2',
            'message' => 'I would like to book: Sea View Hotel',
            'unknown' => 'ignored-value',
        ]))
            ->assertOk()
            ->assertSee('value="Cox&#039;s Bazar"', false)
            ->assertSee('I would like to book: Sea View Hotel')
            ->assertDontSee('ignored-value');
    }

    public function test_signed_in_customer_request_is_linked_to_account(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('enquiries.create', ['service' => 'cabs']))
            ->assertOk()
            ->assertSee('value="'.$user->email.'"', false);

        $this->actingAs($user)
            ->post(route('enquiries.store', ['service' => 'cabs']), [
                ...$this->contactDetails(),
                ...$this->validDetails('cabs'),
                'source_url' => 'https://evil.example/phish',
            ])
            ->assertRedirect();

        $enquiry = Enquiry::query()->sole();
        $this->assertSame($user->id, $enquiry->user_id);
        $this->assertNull($enquiry->source_url);
    }

    public function test_request_submissions_are_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->post(route('enquiries.store', ['service' => 'other']), [])->assertRedirect();
        }

        $this->post(route('enquiries.store', ['service' => 'other']), [])->assertTooManyRequests();
    }

    public function test_holiday_packages_page_links_package_types_to_request_form(): void
    {
        $this->get(route('packages.index'))
            ->assertOk()
            ->assertSee('Holiday Packages from Bangladesh | Eagle Global Hub')
            ->assertSee(route('enquiries.create', ['service' => 'packages', 'package_type' => 'Honeymoon']), false)
            ->assertSee(route('enquiries.create', ['service' => 'packages', 'package_type' => 'Customised']), false)
            ->assertDontSee('BDT');
    }

    /**
     * @return array<string, string>
     */
    private function contactDetails(): array
    {
        return [
            'name' => 'Nadia Rahman',
            'email' => 'Nadia@Example.com',
            'phone' => '+880 1711-000000',
            'message' => 'Please send options.',
        ];
    }

    /**
     * @return array<string, string|int>
     */
    private function validDetails(string $service): array
    {
        $date = now()->addMonth()->toDateString();
        $later = now()->addMonth()->addWeek()->toDateString();

        return match ($service) {
            'flights' => ['from' => 'Dhaka', 'to' => 'Dubai', 'departure_date' => $date, 'return_date' => $later, 'travellers' => 2, 'cabin' => 'Economy'],
            'hotels' => ['city' => 'Dubai', 'check_in' => $date, 'check_out' => $later, 'rooms' => 1, 'guests' => 2],
            'tours' => ['destination' => 'Bali', 'travel_date' => $date, 'guests' => 3],
            'visa' => ['nationality' => 'Bangladesh', 'destination' => 'Japan', 'travel_date' => $date, 'visa_type' => 'Tourist'],
            'packages' => ['package_type' => 'Honeymoon', 'destination' => 'Maldives', 'travel_month' => now()->addMonths(2)->format('Y-m'), 'nights' => 5, 'travellers' => 2, 'budget' => 'BDT 200,000'],
            'trains', 'buses' => ['from' => 'Dhaka', 'to' => 'Chattogram', 'travel_date' => $date, 'passengers' => 2],
            'cabs' => ['pickup' => 'Hazrat Shahjalal Airport', 'dropoff' => 'Gulshan 2', 'pickup_date' => $date, 'pickup_time' => '14:30', 'passengers' => 3],
            'insurance' => ['destination' => 'Schengen area', 'start_date' => $date, 'end_date' => $later, 'travellers' => 2, 'ages' => '34, 31'],
            'other' => ['topic' => 'Group travel for 20 people'],
        };
    }
}
