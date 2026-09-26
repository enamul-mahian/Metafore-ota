<?php

namespace App\Enums;

enum WorkVisaApplicationStatus: string
{
    case Submitted = 'submitted';
    case DocumentsReview = 'documents_review';
    case InProcess = 'in_process';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::DocumentsReview => 'Documents review',
            self::InProcess => 'In process',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Badge modifier from the public design system (.badge--*).
     */
    public function badge(): string
    {
        return match ($this) {
            self::Submitted => 'info',
            self::DocumentsReview, self::InProcess => 'warn',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'muted',
        };
    }

    /**
     * Whether the applicant may still add documents to the file.
     */
    public function acceptsDocuments(): bool
    {
        return in_array($this, [self::Submitted, self::DocumentsReview], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
