<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_ARCHIVED];

    /** @var list<string> */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'country_id',
        'date_of_birth', 'reference_code', 'status', 'notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['country_id' => 'integer', 'date_of_birth' => 'date'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
