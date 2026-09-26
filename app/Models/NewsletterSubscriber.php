<?php

namespace App\Models;

use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_SUBSCRIBED,
        self::STATUS_UNSUBSCRIBED,
    ];

    /** @var list<string> */
    protected $fillable = [
        'email',
        'status',
        'source',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_SUBSCRIBED,
    ];
}
