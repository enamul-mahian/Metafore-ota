<?php

namespace Tests\Feature\WorkVisa;

use App\Models\Country;
use App\Models\WorkVisaDestination;
use App\Services\Feature\FeatureManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkVisaPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_sees_only_active_destinations(): void
    {
        $canada = WorkVisaDestination::factory()->create([
            'country_id' => $this->country('Canada', 'CA', 'CAN')->id,
            'slug' => 'canada',
            'processing_time' => '8–12 weeks',
            'job_categories' => ['Nurse / Caregiver'],
        ]);
        WorkVisaDestination::factory()->inactive()->create([
            'country_id' => $this->country('Romania', 'RO', 'ROU')->id,
            'slug' => 'romania',
        ]);

        $this->get(route('work-visa.index'))
            ->assertOk()
            ->assertSee('Work Visa Processing')
            ->assertSee('Canada')
            ->assertSee('8–12 weeks')
            ->assertSee('Nurse / Caregiver')
            ->assertSee(route('work-visa.apply', ['destination' => $canada->slug]), false)
            ->assertDontSee('Romania')
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_fee_is_only_shown_when_set(): void
    {
        WorkVisaDestination::factory()->create(['country_id' => $this->country('Canada', 'CA', 'CAN')->id]);

        $this->get(route('work-visa.index'))
            ->assertOk()
            ->assertDontSee('Service fee from')
            ->assertDontSee('BDT');

        WorkVisaDestination::factory()->withFee(75000)->create(['country_id' => $this->country('Japan', 'JP', 'JPN')->id]);

        $this->get(route('work-visa.index'))
            ->assertOk()
            ->assertSee('Service fee from')
            ->assertSee('BDT 75,000');
    }

    public function test_empty_state_is_shown_without_active_destinations(): void
    {
        $this->get(route('work-visa.index'))
            ->assertOk()
            ->assertSee('Online applications are not open yet')
            ->assertDontSee('href="'.route('work-visa.apply').'"', false)
            ->assertDontSee('+880')
            ->assertDontSee('5,000+');
    }

    public function test_disabled_feature_hides_the_landing_page(): void
    {
        app(FeatureManager::class)->update('work-visa', [
            'enabled' => false,
            'public_visible' => true,
            'authenticated_visible' => true,
            'admin_visible' => true,
            'message' => null,
        ]);

        $this->get(route('work-visa.index'))->assertNotFound();
    }

    private function country(string $name, string $iso2, string $iso3): Country
    {
        return Country::query()->create(['name' => $name, 'iso2' => $iso2, 'iso3' => $iso3, 'is_active' => true]);
    }
}
