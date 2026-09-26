<?php

namespace App\Models;

use App\Enums\WorkVisaApplicationStatus;
use Database\Factories\WorkVisaApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkVisaApplication extends Model
{
    /** @use HasFactory<WorkVisaApplicationFactory> */
    use HasFactory;

    public const GENDERS = ['male', 'female', 'other'];

    public const TITLES = ['Mr.', 'Mrs.', 'Ms.'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reference',
        'user_id',
        'work_visa_destination_id',
        'job_category',
        'title',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'nationality',
        'passport_number',
        'passport_expiry',
        'phone',
        'email',
        'current_address',
        'experience_years',
        'notes',
        'status',
        'admin_note',
    ];

    /**
     * Generate a unique, human-friendly reference such as EGH-WV-2026-4F7K2Q.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'EGH-WV-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WorkVisaDestination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(WorkVisaDestination::class, 'work_visa_destination_id');
    }

    /**
     * @return HasMany<WorkVisaDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(WorkVisaDocument::class);
    }

    /**
     * @return HasMany<WorkVisaApplicationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(WorkVisaApplicationEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function fullName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->first_name.' '.$this->last_name);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_expiry' => 'date',
            'experience_years' => 'integer',
            'status' => WorkVisaApplicationStatus::class,
        ];
    }
}
