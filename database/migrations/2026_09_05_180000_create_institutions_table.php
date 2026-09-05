<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 200);
            $table->string('email')->nullable()->unique();
            $table->string('phone', 32)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->string('registration_number', 100)->nullable()->unique();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address', 500)->nullable();
            // Operational profile state only; no admissions, contracts, or financial terms.
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
