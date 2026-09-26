<?php

namespace Database\Factories;

use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkVisaDocument>
 */
class WorkVisaDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'work_visa_application_id' => WorkVisaApplication::factory(),
            'type' => 'passport',
            'original_name' => 'passport.pdf',
            'path' => 'work-visa/0/'.Str::random(20).'.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
