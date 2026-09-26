<?php

namespace App\Models;

use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A quote request or contact message sent from the public website.
 *
 * @property array<string, string|int|null>|null $details
 */
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_CLOSED,
    ];

    public const SERVICE_CONTACT = 'contact';

    /** @var list<string> */
    protected $fillable = [
        'service',
        'name',
        'email',
        'phone',
        'message',
        'details',
        'status',
        'user_id',
        'source_url',
        'admin_note',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'user_id' => 'integer',
        ];
    }

    /**
     * Every service value an enquiry may carry (request forms + contact).
     *
     * @return list<string>
     */
    public static function services(): array
    {
        return [
            ...array_keys(config('enquiries.services', [])),
            ...array_keys(config('enquiries.extra_service_labels', [])),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function serviceLabels(): array
    {
        return [
            ...collect(config('enquiries.services', []))->map(fn (array $service): string => $service['label'])->all(),
            ...config('enquiries.extra_service_labels', []),
        ];
    }

    public function serviceLabel(): string
    {
        return self::serviceLabels()[$this->service] ?? ucfirst($this->service);
    }

    public function statusLabel(): string
    {
        return config('enquiries.statuses.'.$this->status, ucfirst($this->status));
    }

    /**
     * Details paired with their human-readable labels, in form order.
     *
     * @return list<array{label: string, value: string}>
     */
    public function labelledDetails(): array
    {
        $definitions = $this->service === self::SERVICE_CONTACT
            ? config('enquiries.contact_fields', [])
            : config('enquiries.services.'.$this->service.'.fields', []);
        $details = $this->details ?? [];
        $rows = [];

        foreach ($definitions as $key => $definition) {
            if (($details[$key] ?? null) !== null && $details[$key] !== '') {
                $rows[] = ['label' => $definition['label'], 'value' => (string) $details[$key]];
            }
        }

        foreach (array_diff_key($details, $definitions) as $key => $value) {
            if ($value !== null && $value !== '' && is_scalar($value)) {
                $rows[] = ['label' => ucfirst(str_replace('_', ' ', (string) $key)), 'value' => (string) $value];
            }
        }

        return $rows;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
