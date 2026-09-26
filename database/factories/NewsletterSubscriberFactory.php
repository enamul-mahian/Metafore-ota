<?php

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'source' => 'footer',
        ];
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (): array => ['status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED]);
    }
}
