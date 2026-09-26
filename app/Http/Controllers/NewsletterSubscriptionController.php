<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NewsletterSubscriptionController extends Controller
{
    public const SUCCESS_MESSAGE = 'Thank you for subscribing. We will email you travel updates and offers.';

    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse|JsonResponse
    {
        if (! $request->filled(StoreEnquiryRequest::HONEYPOT)) {
            $validated = $request->validated();

            $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $validated['email']]);
            $subscriber->status = NewsletterSubscriber::STATUS_SUBSCRIBED;
            $subscriber->source ??= $validated['source'] ?? null;
            $subscriber->save();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => self::SUCCESS_MESSAGE]);
        }

        return back()->with('newsletter_subscribed', self::SUCCESS_MESSAGE);
    }
}
