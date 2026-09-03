<?php

namespace Tests\Feature\Flight;

use App\Contracts\Flight\FlightSearchProvider;
use App\Services\Flight\FixtureFlightSearchProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FixtureFlightSearchProviderTest extends TestCase
{
    public function test_fixture_provider_can_be_resolved_from_configuration(): void
    {
        config()->set(
            'flight.search_provider',
            'fixture'
        );

        $provider = $this->app->make(
            FlightSearchProvider::class
        );

        $this->assertInstanceOf(
            FixtureFlightSearchProvider::class,
            $provider
        );
    }

    public function test_fixture_provider_returns_demo_offers_without_http_requests(): void
    {
        Http::fake();

        $offers = (new FixtureFlightSearchProvider)
            ->search($this->criteria());

        $this->assertCount(2, $offers);

        $this->assertSame(
            'fixture',
            $offers[0]['provider']
        );

        $this->assertSame(
            'BDT',
            $offers[0]['total_currency']
        );

        $this->assertSame(
            'Eagle Global Hub LTD Demo Air',
            $offers[0]['owner']['name']
        );

        $this->assertCount(
            1,
            $offers[0]['slices']
        );

        $this->assertSame(
            'DAC',
            $offers[0]['slices'][0]['origin']['iata_code']
        );

        $this->assertSame(
            'DXB',
            $offers[0]['slices'][0]['destination']['iata_code']
        );

        Http::assertNothingSent();
    }

    public function test_round_trip_fixture_contains_return_slice(): void
    {
        $offers = (new FixtureFlightSearchProvider)
            ->search(
                $this->criteria([
                    'trip_type' => 'round_trip',
                    'return_date' => '2030-06-20',
                ])
            );

        $this->assertCount(
            2,
            $offers[0]['slices']
        );

        $this->assertSame(
            'DXB',
            $offers[0]['slices'][1]['origin']['iata_code']
        );

        $this->assertSame(
            'DAC',
            $offers[0]['slices'][1]['destination']['iata_code']
        );
    }

    public function test_fixture_pricing_changes_with_passengers_and_cabin(): void
    {
        $provider = new FixtureFlightSearchProvider;

        $economy = (float) $provider->search(
            $this->criteria()
        )[0]['total_amount'];

        $business = (float) $provider->search(
            $this->criteria([
                'cabin_class' => 'business',
            ])
        )[0]['total_amount'];

        $twoAdults = (float) $provider->search(
            $this->criteria([
                'adults' => 2,
            ])
        )[0]['total_amount'];

        $this->assertGreaterThan(
            $economy,
            $business
        );

        $this->assertGreaterThan(
            $economy,
            $twoAdults
        );
    }

    public function test_fixture_offer_ids_are_deterministic_for_same_search(): void
    {
        $provider = new FixtureFlightSearchProvider;
        $criteria = $this->criteria();

        $first = $provider->search($criteria);
        $second = $provider->search($criteria);

        $this->assertSame(
            $first[0]['id'],
            $second[0]['id']
        );

        $this->assertSame(
            $first[1]['id'],
            $second[1]['id']
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function criteria(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'trip_type' => 'one_way',
                'origin' => 'DAC',
                'destination' => 'DXB',
                'departure_date' => '2030-06-10',
                'return_date' => null,
                'adults' => 1,
                'children' => 0,
                'infants' => 0,
                'cabin_class' => 'economy',
            ],
            $overrides
        );
    }
}
