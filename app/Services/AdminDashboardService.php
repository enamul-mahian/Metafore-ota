<?php

namespace App\Services;

use App\Enums\WorkVisaApplicationStatus;
use App\Models\Enquiry;
use App\Models\FlightOrderAttempt;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\WorkVisaApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Real counts for the admin dashboard, limited to what the viewer may see.
 */
class AdminDashboardService
{
    /**
     * @return list<array{label: string, value: int, hint: string, icon: string, tone: string, url: ?string}>
     */
    public function kpis(User $user): array
    {
        $kpis = [];

        if ($user->can('enquiries.view')) {
            $kpis[] = [
                'label' => 'New enquiries',
                'value' => Enquiry::query()->where('status', Enquiry::STATUS_NEW)->count(),
                'hint' => Enquiry::query()->where('created_at', '>=', now()->subDays(7))->count().' in the last 7 days',
                'icon' => 'mail',
                'tone' => 'blue',
                'url' => $this->url('admin.enquiries.index', ['status' => Enquiry::STATUS_NEW]),
            ];
        }

        if ($user->can('work-visa.view')) {
            $open = [WorkVisaApplicationStatus::Submitted->value, WorkVisaApplicationStatus::DocumentsReview->value, WorkVisaApplicationStatus::InProcess->value];
            $kpis[] = [
                'label' => 'Open work visa files',
                'value' => WorkVisaApplication::query()->whereIn('status', $open)->count(),
                'hint' => WorkVisaApplication::query()->where('status', WorkVisaApplicationStatus::Submitted->value)->count().' waiting for first review',
                'icon' => 'briefcase',
                'tone' => 'amber',
                'url' => $this->url('admin.work-visa.applications.index'),
            ];
        }

        $kpis[] = [
            'label' => 'Flight bookings',
            'value' => FlightOrderAttempt::query()->where('status', FlightOrderAttempt::STATUS_CREATED)->count(),
            'hint' => FlightOrderAttempt::query()->where('created_at', '>=', now()->subDays(30))->count().' attempts in the last 30 days',
            'icon' => 'plane',
            'tone' => 'indigo',
            'url' => $this->url('admin.bookings.index'),
        ];

        if ($user->can('users.view')) {
            $kpis[] = [
                'label' => 'Customers',
                'value' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'customer'))->count(),
                'hint' => User::query()->where('created_at', '>=', now()->subDays(30))->count().' new accounts in 30 days',
                'icon' => 'users',
                'tone' => 'green',
                'url' => $this->url('admin.users.index'),
            ];
        }

        if ($user->can('enquiries.view')) {
            $kpis[] = [
                'label' => 'Newsletter subscribers',
                'value' => NewsletterSubscriber::query()->where('status', NewsletterSubscriber::STATUS_SUBSCRIBED)->count(),
                'hint' => 'Active email subscribers',
                'icon' => 'send',
                'tone' => 'sky',
                'url' => $this->url('admin.newsletter.index'),
            ];
        }

        return $kpis;
    }

    /**
     * @return Collection<int, Enquiry>
     */
    public function recentEnquiries(User $user, int $limit = 6): Collection
    {
        if (! $user->can('enquiries.view')) {
            return collect();
        }

        return Enquiry::query()->latest()->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function url(string $routeName, array $parameters = []): ?string
    {
        return Route::has($routeName) ? route($routeName, $parameters) : null;
    }
}
