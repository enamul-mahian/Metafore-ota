<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('email')->unique();
            $table->string('phone', 32)->nullable();
            $table->string('organization_name', 150)->nullable();
            $table->string('referral_code', 64)->unique();
            $table->string('website_url', 2048)->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            // Operational lifecycle only; no commission, balance, or payout terms are implied.
            $table->string('status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
