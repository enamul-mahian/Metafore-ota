<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affiliate extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    /** @var list<string> */
    protected $fillable = [
        'name', 'email', 'phone', 'organization_name', 'referral_code',
        'website_url', 'country_id', 'status', 'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['country_id' => 'integer'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
