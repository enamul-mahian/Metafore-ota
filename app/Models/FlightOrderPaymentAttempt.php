<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FlightOrderPaymentAttempt extends Model
{
    public const STATUS_PROCESSING =
        'processing';

    public const STATUS_SUCCEEDED =
        'succeeded';

    public const STATUS_FAILED =
        'failed';

    protected $fillable = [
        'user_id',
        'flight_order_attempt_id',
        'reference_hash',
        'payment_identity_hash',
        'provider',
        'payment_type',
        'amount',
        'currency',
        'status',
        'supplier_payment_id',
        'resolved_at',
    ];

    protected $hidden = [
        'reference_hash',
        'payment_identity_hash',
    ];

    protected function casts(): array
    {
        return [
            'user_id' =>
                'integer',

            'flight_order_attempt_id' =>
                'integer',

            'resolved_at' =>
                'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }

    public function flightOrderAttempt(): BelongsTo
    {
        return $this->belongsTo(
            FlightOrderAttempt::class,
        );
    }
}