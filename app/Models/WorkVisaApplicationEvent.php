<?php

namespace App\Models;

use App\Enums\WorkVisaApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkVisaApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'work_visa_application_id',
        'status',
        'note',
        'user_id',
    ];

    /**
     * @return BelongsTo<WorkVisaApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(WorkVisaApplication::class, 'work_visa_application_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkVisaApplicationStatus::class,
        ];
    }
}
