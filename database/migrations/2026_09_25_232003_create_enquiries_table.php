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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('service', 32)->index();
            $table->string('name', 120);
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->string('status', 16)->default('new')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_url', 2048)->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
