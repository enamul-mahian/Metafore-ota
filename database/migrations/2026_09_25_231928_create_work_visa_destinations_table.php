<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_visa_destinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('slug', 120)->unique();
            $table->string('visa_type', 150)->nullable();
            $table->string('summary', 500)->nullable();
            $table->json('job_categories')->nullable();
            $table->string('processing_time', 100)->nullable();
            $table->decimal('service_fee', 12, 2)->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->json('requirements')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_visa_destinations');
    }
};
