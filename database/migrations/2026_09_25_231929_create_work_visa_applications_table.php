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
        Schema::create('work_visa_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_visa_destination_id')->constrained()->restrictOnDelete();
            $table->string('job_category', 150);
            $table->string('title', 10)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('nationality', 100);
            $table->string('passport_number', 20);
            $table->date('passport_expiry');
            $table->string('phone', 32);
            $table->string('email');
            $table->string('current_address', 500);
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('submitted');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('passport_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_visa_applications');
    }
};
