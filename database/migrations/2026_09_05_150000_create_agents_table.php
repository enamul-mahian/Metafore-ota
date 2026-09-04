<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('email')->unique();
            $table->string('phone', 32)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('registration_number', 100)->nullable()->unique();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();

            // Provisional operational lifecycle only; no financial meaning is implied.
            $table->string('status', 32)->default('pending');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
