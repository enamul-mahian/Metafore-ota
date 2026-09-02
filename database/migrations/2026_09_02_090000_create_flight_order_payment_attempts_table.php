<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'flight_order_payment_attempts',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'user_id',
                )
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreignId(
                    'flight_order_attempt_id',
                )
                    ->unique()
                    ->constrained(
                        'flight_order_attempts',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->char(
                    'reference_hash',
                    64,
                )->unique();

                $table->char(
                    'payment_identity_hash',
                    64,
                )->unique();

                $table->string(
                    'provider',
                    64,
                );

                $table->string(
                    'payment_type',
                    32,
                );

                $table->string(
                    'amount',
                    32,
                );

                $table->char(
                    'currency',
                    3,
                );

                $table->string(
                    'status',
                    32,
                )->default(
                    'processing',
                );

                $table->string(
                    'supplier_payment_id',
                    255,
                )->nullable();

                $table->timestamp(
                    'resolved_at',
                )->nullable();

                $table->timestamps();

                $table->index([
                    'user_id',
                    'status',
                ]);

                $table->index([
                    'provider',
                    'status',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'flight_order_payment_attempts',
        );
    }
};