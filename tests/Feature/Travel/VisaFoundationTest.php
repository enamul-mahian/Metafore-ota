<?php

namespace Tests\Feature\Travel;

use App\Contracts\Visa\VisaInformationProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisaFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_visa_service(): void
    {
        $this->get(route('visa.index'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_customer_sees_safe_unconfigured_state(): void
    {
        $this->actingAs($this->customer())
            ->get(route('visa.index'))
            ->assertOk()
            ->assertSee('Visa information service is not configured')
            ->assertSee('Not Configured')
            ->assertSee('Approval is never guaranteed')
            ->assertDontSee('Check Requirements');
    }

    public function test_user_without_permission_cannot_access_visa_service(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('visa.index'))
            ->assertForbidden();
    }

    public function test_unconfigured_requirement_lookup_fails_safely(): void
    {
        $this->actingAs($this->customer())
            ->post(route('visa.requirements'), $this->validRequest())
            ->assertServiceUnavailable()
            ->assertDontSee('eligible')
            ->assertDontSee('approved');
    }

    public function test_configured_source_can_return_an_honest_no_data_state(): void
    {
        config()->set('travel_services.services.visa.enabled', true);
        config()->set(
            'travel_services.services.visa.provider',
            'test-provider'
        );
        config()->set(
            'travel_services.services.visa.providers.test-provider',
            EmptyVisaInformationProvider::class
        );
        config()->set(
            'travel_services.services.visa.provider_requirements.test-provider',
            ['credentials.api_key']
        );
        config()->set(
            'travel_services.services.visa.credentials.api_key',
            'server-only-test-key'
        );

        $this->app->forgetInstance(VisaInformationProvider::class);

        $this->actingAs($this->customer())
            ->post(route('visa.requirements'), $this->validRequest())
            ->assertOk()
            ->assertSee('No visa information was returned')
            ->assertSeeText('Eligibility, documents and approval have not been assumed')
            ->assertDontSee('server-only-test-key');
    }

    public function test_visa_requirement_input_is_validated_before_provider_use(): void
    {
        $this->actingAs($this->customer())
            ->post(route('visa.requirements'), [
                'nationality' => 'Bangladesh',
                'destination_country' => 'Bangladesh',
                'visa_type' => '',
            ])
            ->assertSessionHasErrors([
                'destination_country',
                'visa_type',
            ]);
    }

    private function customer(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function validRequest(): array
    {
        return [
            'nationality' => 'Bangladesh',
            'destination_country' => 'United Arab Emirates',
            'visa_type' => 'Tourist',
        ];
    }
}

class EmptyVisaInformationProvider implements VisaInformationProvider
{
    public function requirements(array $criteria): array
    {
        return [];
    }
}
