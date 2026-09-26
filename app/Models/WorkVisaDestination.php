<?php

namespace App\Models;

use Database\Factories\WorkVisaDestinationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkVisaDestination extends Model
{
    /** @use HasFactory<WorkVisaDestinationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'country_id',
        'slug',
        'visa_type',
        'summary',
        'job_categories',
        'processing_time',
        'service_fee',
        'currency',
        'requirements',
        'is_active',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<WorkVisaApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(WorkVisaApplication::class);
    }

    /**
     * @param  Builder<WorkVisaDestination>  $query
     * @return Builder<WorkVisaDestination>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<WorkVisaDestination>  $query
     * @return Builder<WorkVisaDestination>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function name(): string
    {
        return $this->country?->name ?? 'Destination';
    }

    /**
     * Flag emoji built from the country's ISO 3166-1 alpha-2 code.
     */
    public function flag(): string
    {
        $iso2 = strtoupper((string) $this->country?->iso2);

        if (preg_match('/^[A-Z]{2}$/', $iso2) !== 1) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($iso2[0]) - 65).mb_chr(0x1F1E6 + ord($iso2[1]) - 65);
    }

    public function formattedFee(): ?string
    {
        if ($this->service_fee === null) {
            return null;
        }

        $amount = (float) $this->service_fee;
        $decimals = floor($amount) === $amount ? 0 : 2;

        return $this->currency.' '.number_format($amount, $decimals);
    }

    /**
     * @return list<string>
     */
    public function jobCategoryList(): array
    {
        return array_values(array_filter((array) $this->job_categories, 'is_string'));
    }

    /**
     * @return list<string>
     */
    public function requirementList(): array
    {
        return array_values(array_filter((array) $this->requirements, 'is_string'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_categories' => 'array',
            'requirements' => 'array',
            'service_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
