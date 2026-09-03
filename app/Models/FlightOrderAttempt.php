<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class FlightOrderAttempt extends Model
{
    public const STATUS_PROCESSING =
        'processing';

    public const STATUS_CREATED =
        'created';

    public const STATUS_FAILED =
        'failed';

    protected $fillable = [
        'user_id',
        'reference_hash',
        'attempt_identity_hash',
        'provider',
        'supplier_offer_id',
        'status',
        'supplier_order_id',
        'resolved_at',
    ];

    protected $hidden = [
        'reference_hash',
        'attempt_identity_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',

            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }

    public function paymentAttempt(): HasOne
    {
        return $this->hasOne(
            FlightOrderPaymentAttempt::class,
        );
    }
}
