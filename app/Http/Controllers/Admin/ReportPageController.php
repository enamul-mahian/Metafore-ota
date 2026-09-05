<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\Agent;
use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ReportPageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($request->filled('from'), 'after_or_equal:from'),
            ],
        ]);

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $bookingQuery = $this->withDateRange(
            FlightOrderAttempt::query(),
            $from,
            $to,
        );
        $paymentQuery = $this->withDateRange(
            FlightOrderPaymentAttempt::query(),
            $from,
            $to,
        );

        $bookingStatuses = [
            FlightOrderAttempt::STATUS_PROCESSING,
            FlightOrderAttempt::STATUS_CREATED,
            FlightOrderAttempt::STATUS_FAILED,
        ];
        $paymentStatuses = [
            FlightOrderPaymentAttempt::STATUS_PROCESSING,
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            FlightOrderPaymentAttempt::STATUS_FAILED,
        ];

        $successfulPaymentVolumes = (clone $paymentQuery)
            ->where('status', FlightOrderPaymentAttempt::STATUS_SUCCEEDED)
            ->select('currency')
            ->selectRaw(
                'SUM(CAST(amount AS DECIMAL(40, 8))) AS aggregate'
            )
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(fn (FlightOrderPaymentAttempt $volume): array => [
                'currency' => $volume->currency,
                'amount' => number_format(
                    (float) $volume->getAttribute('aggregate'),
                    2,
                    '.',
                    ',',
                ),
            ]);

        $recentBookings = $this->withDateRange(
            FlightOrderAttempt::query(),
            $from,
            $to,
        )
            ->with([
                'user:id,name,email',
                'paymentAttempt:id,flight_order_attempt_id,status',
            ])
            ->latest()
            ->orderByDesc('id')
            ->limit(10)
            ->get([
                'id',
                'user_id',
                'status',
                'created_at',
            ]);

        $recentSuccessfulPayments = $this->withDateRange(
            FlightOrderPaymentAttempt::query(),
            $from,
            $to,
        )
            ->where('status', FlightOrderPaymentAttempt::STATUS_SUCCEEDED)
            ->latest('resolved_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get([
                'id',
                'amount',
                'currency',
                'resolved_at',
                'created_at',
            ]);

        return view('admin.reports.index', [
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'bookingTotal' => (clone $bookingQuery)->count(),
            'bookingStatusCounts' => $this->statusCounts(
                $bookingQuery,
                $bookingStatuses,
            ),
            'paymentTotal' => (clone $paymentQuery)->count(),
            'paymentStatusCounts' => $this->statusCounts(
                $paymentQuery,
                $paymentStatuses,
            ),
            'successfulPaymentVolumes' => $successfulPaymentVolumes,
            'profileSummaries' => [
                'Agents' => $this->profileSummary(Agent::query(), Agent::STATUSES),
                'Affiliates' => $this->profileSummary(Affiliate::query(), Affiliate::STATUSES),
                'Students' => $this->profileSummary(Student::query(), Student::STATUSES),
                'Institutions' => $this->profileSummary(Institution::query(), Institution::STATUSES),
            ],
            'recentBookings' => $recentBookings,
            'recentSuccessfulPayments' => $recentSuccessfulPayments,
        ]);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function withDateRange(
        Builder $query,
        ?string $from,
        ?string $to,
    ): Builder {
        return $query
            ->when(
                $from,
                fn (Builder $query, string $date): Builder => $query->whereDate(
                    'created_at',
                    '>=',
                    $date,
                ),
            )
            ->when(
                $to,
                fn (Builder $query, string $date): Builder => $query->whereDate(
                    'created_at',
                    '<=',
                    $date,
                ),
            );
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $statuses
     * @return array<string, int>
     */
    private function statusCounts(Builder $query, array $statuses): array
    {
        $persistedCounts = (clone $query)
            ->whereIn('status', $statuses)
            ->select('status')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect($statuses)
            ->mapWithKeys(fn (string $status): array => [
                $status => (int) ($persistedCounts[$status] ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Builder<Model>  $query
     * @param  list<string>  $statuses
     * @return array{total: int, statuses: array<string, int>}
     */
    private function profileSummary(Builder $query, array $statuses): array
    {
        return [
            'total' => (clone $query)->count(),
            'statuses' => $this->statusCounts($query, $statuses),
        ];
    }
}
