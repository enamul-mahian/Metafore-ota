<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\WorkVisaDestination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkVisaDestination>
 */
class WorkVisaDestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => fn (): int => Country::query()->create([
                'name' => 'Testland '.Str::upper(Str::random(4)),
                'iso2' => $this->uniqueCode(2),
                'iso3' => $this->uniqueCode(3),
                'is_active' => true,
            ])->id,
            'slug' => 'destination-'.Str::lower(Str::random(8)),
            'visa_type' => 'Skilled Worker Visa',
            'summary' => fake()->sentence(),
            'job_categories' => ['Construction', 'Hospitality'],
            'processing_time' => '8–12 weeks',
            'service_fee' => null,
            'currency' => 'BDT',
            'requirements' => ['Valid passport', 'Passport-size photo'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withFee(float $amount, string $currency = 'BDT'): static
    {
        return $this->state(fn (): array => ['service_fee' => $amount, 'currency' => $currency]);
    }

    private function uniqueCode(int $length): string
    {
        $column = $length === 2 ? 'iso2' : 'iso3';

        do {
            $code = Str::upper(Str::random($length));
        } while (preg_match('/^[A-Z]+$/', $code) !== 1 || Country::query()->where($column, $code)->exists());

        return $code;
    }
}
