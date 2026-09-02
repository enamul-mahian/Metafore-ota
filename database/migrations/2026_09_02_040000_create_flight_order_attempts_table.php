<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'flight_order_attempts',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'user_id',
                )
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                /*
                 * Persist only a SHA-256 digest of the opaque client-safe
                 * reference. The raw reference is returned once and is not
                 * stored in this table.
                 */
                $table->char(
                    'reference_hash',
                    64,
                )->unique();

                /*
                 * Stable provider + supplier-offer identity.
                 * This prevents duplicate durable attempt records for the
                 * same trusted supplier create-order attempt.
                 */
                $table->char(
                    'attempt_identity_hash',
                    64,
                )->unique();

                $table->string(
                    'provider',
                    64,
                );

                $table->string(
                    'supplier_offer_id',
                    255,
                );

                $table->string(
                    'status',
                    32,
                )->default(
                    'processing',
                );

                /*
                 * A 202 response does not yet provide a completed supplier
                 * order ID. This remains null until a later verified
                 * reconciliation boundary resolves the attempt.
                 */
                $table->string(
                    'supplier_order_id',
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
            'flight_order_attempts',
        );
    }
};