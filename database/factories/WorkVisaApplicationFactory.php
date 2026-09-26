<?php

namespace Database\Factories;

use App\Enums\WorkVisaApplicationStatus;
use App\Models\User;
use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDestination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkVisaApplication>
 */
class WorkVisaApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'EGH-WV-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'work_visa_destination_id' => WorkVisaDestination::factory(),
            'job_category' => 'Construction',
            'title' => 'Mr.',
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => now()->subYears(28)->toDateString(),
            'gender' => 'male',
            'nationality' => 'Bangladeshi',
            'passport_number' => 'A'.fake()->numerify('########'),
            'passport_expiry' => now()->addYears(3)->toDateString(),
            'phone' => '+8801'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'current_address' => fake()->address(),
            'experience_years' => 3,
            'notes' => null,
            'status' => WorkVisaApplicationStatus::Submitted,
            'admin_note' => null,
        ];
    }

    public function status(WorkVisaApplicationStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
