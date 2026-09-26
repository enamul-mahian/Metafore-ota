<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        if (! in_array($status, NewsletterSubscriber::STATUSES, true)) {
            $status = '';
        }

        $subscribers = NewsletterSubscriber::query()
            ->when($search !== '', fn ($query) => $query->where('email', 'like', '%'.$search.'%'))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.newsletter.index', [
            'subscribers' => $subscribers,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => NewsletterSubscriber::STATUSES,
            'subscribedCount' => NewsletterSubscriber::query()->where('status', NewsletterSubscriber::STATUS_SUBSCRIBED)->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $status = (string) $request->query('status', NewsletterSubscriber::STATUS_SUBSCRIBED);

        $query = NewsletterSubscriber::query()
            ->when(in_array($status, NewsletterSubscriber::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'status', 'source', 'subscribed_at'], escape: '');

            $query->chunk(500, function ($subscribers) use ($handle): void {
                foreach ($subscribers as $subscriber) {
                    fputcsv($handle, [
                        self::safeCell($subscriber->email),
                        $subscriber->status,
                        self::safeCell((string) $subscriber->source),
                        $subscriber->created_at?->toIso8601String(),
                    ], escape: '');
                }
            });

            fclose($handle);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Stop spreadsheet apps from treating a value as a formula.
     */
    private static function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
