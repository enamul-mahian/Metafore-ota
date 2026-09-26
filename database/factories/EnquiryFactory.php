<?php

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service' => 'hotels',
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('01#########'),
            'message' => fake()->optional()->sentence(),
            'details' => [
                'city' => fake()->city(),
                'check_in' => now()->addWeeks(2)->toDateString(),
                'check_out' => now()->addWeeks(2)->addDays(3)->toDateString(),
                'rooms' => 1,
                'guests' => 2,
            ],
            'status' => Enquiry::STATUS_NEW,
        ];
    }

    public function contact(): static
    {
        return $this->state(fn (): array => [
            'service' => Enquiry::SERVICE_CONTACT,
            'message' => fake()->paragraph(),
            'details' => ['subject' => fake()->sentence(4)],
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
