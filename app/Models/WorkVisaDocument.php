<?php

namespace App\Models;

use Database\Factories\WorkVisaDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class WorkVisaDocument extends Model
{
    /** @use HasFactory<WorkVisaDocumentFactory> */
    use HasFactory;

    /**
     * Private storage disk. Work visa documents are never served publicly.
     */
    public const DISK = 'local';

    public const MAX_KILOBYTES = 5120;

    public const MIMES = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * @var array<string, string>
     */
    public const TYPES = [
        'passport' => 'Passport (bio page)',
        'photo' => 'Passport-size photo',
        'cv' => 'CV / Resume',
        'certificate' => 'Certificate',
        'other' => 'Other document',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'work_visa_application_id',
        'type',
        'original_name',
        'path',
        'mime',
        'size',
    ];

    /**
     * @return BelongsTo<WorkVisaApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(WorkVisaApplication::class, 'work_visa_application_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }
}
