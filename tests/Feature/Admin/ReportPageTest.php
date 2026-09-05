<?php

namespace Tests\Feature\Admin;

use App\Models\Affiliate;
use App\Models\Agent;
use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_reports(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_is_forbidden_and_never_sees_admin_reports(): void
    {
        $customer = $this->user('customer');

        $this->actingAs($customer)
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.reports.index'), false);
    }

    public function test_admin_and_super_admin_can_view_reports(): void
    {
        foreach (['admin', 'super-admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('admin.reports.index'))
                ->assertOk()
                ->assertSee('Read-only reporting from persisted application records.')
                ->assertSee('Successful payment volume');
        }
    }

    public function test_admin_without_reports_permission_is_forbidden(): void
    {
        $admin = $this->user('admin');
        Role::findByName('admin')->revokePermissionTo('reports.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin->fresh())
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_booking_totals_and_status_breakdown_use_persisted_attempts(): void
    {
        $customer = $this->user('customer');
        $this->createBooking($customer, FlightOrderAttempt::STATUS_PROCESSING);
        $this->createBooking($customer, FlightOrderAttempt::STATUS_CREATED);
        $this->createBooking($customer, FlightOrderAttempt::STATUS_CREATED);
        $this->createBooking($customer, FlightOrderAttempt::STATUS_FAILED);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertViewHas('bookingTotal', 4)
            ->assertViewHas('bookingStatusCounts', [
                FlightOrderAttempt::STATUS_PROCESSING => 1,
                FlightOrderAttempt::STATUS_CREATED => 2,
                FlightOrderAttempt::STATUS_FAILED => 1,
            ]);
    }

    public function test_payment_counts_and_successful_volume_use_persisted_values_by_currency(): void
    {
        $customer = $this->user('customer');
        $processingBooking = $this->createBooking($customer);
        $usdBooking = $this->createBooking($customer);
        $secondUsdBooking = $this->createBooking($customer);
        $bdtBooking = $this->createBooking($customer);
        $failedBooking = $this->createBooking($customer);

        $this->createPayment($customer, $processingBooking, '10.00', 'USD', FlightOrderPaymentAttempt::STATUS_PROCESSING);
        $this->createPayment($customer, $usdBooking, '100.25', 'USD', FlightOrderPaymentAttempt::STATUS_SUCCEEDED);
        $this->createPayment($customer, $secondUsdBooking, '24.75', 'USD', FlightOrderPaymentAttempt::STATUS_SUCCEEDED);
        $this->createPayment($customer, $bdtBooking, '250.00', 'BDT', FlightOrderPaymentAttempt::STATUS_SUCCEEDED);
        $this->createPayment($customer, $failedBooking, '999.00', 'USD', FlightOrderPaymentAttempt::STATUS_FAILED);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertViewHas('paymentTotal', 5)
            ->assertViewHas('paymentStatusCounts', [
                FlightOrderPaymentAttempt::STATUS_PROCESSING => 1,
                FlightOrderPaymentAttempt::STATUS_SUCCEEDED => 3,
                FlightOrderPaymentAttempt::STATUS_FAILED => 1,
            ])
            ->assertViewHas(
                'successfulPaymentVolumes',
                fn (Collection $volumes): bool => $volumes->all() === [
                    ['currency' => 'BDT', 'amount' => '250.00'],
                    ['currency' => 'USD', 'amount' => '125.00'],
                ],
            )
            ->assertSee('BDT 250.00')
            ->assertSee('USD 125.00')
            ->assertDontSee('375.00')
            ->assertDontSee('Grand total');
    }

    public function test_business_profile_totals_and_defined_statuses_are_all_time(): void
    {
        Agent::query()->create($this->agentAttributes('pending', 'one'));
        Agent::query()->create($this->agentAttributes('active', 'two'));
        Affiliate::query()->create($this->affiliateAttributes('inactive', 'one'));
        Student::query()->create($this->studentAttributes('active', 'one'));
        Student::query()->create($this->studentAttributes('archived', 'two'));
        Institution::query()->create($this->institutionAttributes('active', 'one'));
        Institution::query()->create($this->institutionAttributes('inactive', 'two'));
        Institution::query()->create($this->institutionAttributes('archived', 'three'));

        $this->actingAs($this->user('admin'))
            ->get(route('admin.reports.index', [
                'from' => '2099-01-01',
                'to' => '2099-01-31',
            ]))
            ->assertOk()
            ->assertSee('All-time persisted records; date filters do not apply')
            ->assertViewHas('profileSummaries', [
                'Agents' => [
                    'total' => 2,
                    'statuses' => ['pending' => 1, 'active' => 1, 'inactive' => 0],
                ],
                'Affiliates' => [
                    'total' => 1,
                    'statuses' => ['pending' => 0, 'active' => 0, 'inactive' => 1],
                ],
                'Students' => [
                    'total' => 2,
                    'statuses' => ['active' => 1, 'inactive' => 0, 'archived' => 1],
                ],
                'Institutions' => [
                    'total' => 3,
                    'statuses' => ['active' => 1, 'inactive' => 1, 'archived' => 1],
                ],
            ]);
    }

    public function test_date_filter_applies_to_booking_and_payment_metrics(): void
    {
        $customer = $this->user('customer');
        $outsideBooking = $this->createBooking($customer, FlightOrderAttempt::STATUS_FAILED, '2026-07-31 23:59:59');
        $insideBooking = $this->createBooking($customer, FlightOrderAttempt::STATUS_CREATED, '2026-08-15 12:00:00');
        $this->createBooking($customer, FlightOrderAttempt::STATUS_PROCESSING, '2026-09-01 00:00:00');
        $this->createPayment($customer, $outsideBooking, '500.00', 'USD', FlightOrderPaymentAttempt::STATUS_SUCCEEDED, '2026-07-31 23:59:59');
        $this->createPayment($customer, $insideBooking, '75.50', 'USD', FlightOrderPaymentAttempt::STATUS_SUCCEEDED, '2026-08-31 23:59:59');

        $this->actingAs($this->user('admin'))
            ->get(route('admin.reports.index', [
                'from' => '2026-08-01',
                'to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertViewHas('bookingTotal', 1)
            ->assertViewHas('paymentTotal', 1)
            ->assertViewHas(
                'successfulPaymentVolumes',
                fn (Collection $volumes): bool => $volumes->all() === [
                    ['currency' => 'USD', 'amount' => '75.50'],
                ],
            )
            ->assertSee('value="2026-08-01"', false)
            ->assertSee('value="2026-08-31"', false);
    }

    public function test_invalid_date_filters_fail_safely(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->from(route('admin.reports.index'))
            ->get(route('admin.reports.index', ['from' => 'not-a-date']))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('from');

        $this->actingAs($admin)
            ->from(route('admin.reports.index'))
            ->get(route('admin.reports.index', [
                'from' => '2026-09-05',
                'to' => '2026-09-04',
            ]))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('to');
    }

    public function test_navigation_link_is_real_and_permission_gated_on_both_admin_surfaces(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(route('admin.reports.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee(route('admin.reports.index'), false);

        Role::findByName('admin')->revokePermissionTo('reports.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin->fresh())
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertDontSee(route('admin.reports.index'), false);

        $this->actingAs($admin->fresh())
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertDontSee(route('admin.reports.index'), false);
    }

    public function test_sensitive_booking_and_payment_identifiers_are_not_rendered(): void
    {
        $customer = $this->user('customer', [
            'name' => 'Safe Customer',
            'email' => 'safe@example.com',
        ]);
        $booking = $this->createBooking($customer, FlightOrderAttempt::STATUS_CREATED);
        $payment = $this->createPayment(
            $customer,
            $booking,
            '85.00',
            'EUR',
            FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
        );

        $this->actingAs($this->user('admin'))
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Safe Customer')
            ->assertSee('safe@example.com')
            ->assertDontSee($booking->reference_hash)
            ->assertDontSee($booking->attempt_identity_hash)
            ->assertDontSee($booking->supplier_offer_id)
            ->assertDontSee($booking->supplier_order_id)
            ->assertDontSee($payment->reference_hash)
            ->assertDontSee($payment->payment_identity_hash)
            ->assertDontSee($payment->supplier_payment_id);
    }

    public function test_reports_expose_no_mutation_routes(): void
    {
        $this->assertFalse(Route::has('admin.reports.store'));
        $this->assertFalse(Route::has('admin.reports.update'));
        $this->assertFalse(Route::has('admin.reports.destroy'));

        $this->actingAs($this->user('admin'))
            ->post(route('admin.reports.index'))
            ->assertMethodNotAllowed();
    }

    /** @param array<string, mixed> $attributes */
    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    private function createBooking(
        User $user,
        string $status = FlightOrderAttempt::STATUS_PROCESSING,
        ?string $createdAt = null,
    ): FlightOrderAttempt {
        $unique = uniqid('', true);
        $booking = FlightOrderAttempt::query()->create([
            'user_id' => $user->id,
            'reference_hash' => hash('sha256', 'reference-'.$unique),
            'attempt_identity_hash' => hash('sha256', 'identity-'.$unique),
            'provider' => 'duffel',
            'supplier_offer_id' => 'off_secret_'.$unique,
            'status' => $status,
            'supplier_order_id' => 'ord_secret_'.$unique,
            'resolved_at' => $status === FlightOrderAttempt::STATUS_PROCESSING ? null : now(),
        ]);

        if ($createdAt !== null) {
            FlightOrderAttempt::query()
                ->whereKey($booking->id)
                ->update(['created_at' => $createdAt]);
            $booking->refresh();
        }

        return $booking;
    }

    private function createPayment(
        User $user,
        FlightOrderAttempt $booking,
        string $amount,
        string $currency,
        string $status,
        ?string $createdAt = null,
    ): FlightOrderPaymentAttempt {
        $unique = uniqid('', true);
        $payment = FlightOrderPaymentAttempt::query()->create([
            'user_id' => $user->id,
            'flight_order_attempt_id' => $booking->id,
            'reference_hash' => hash('sha256', 'payment-reference-'.$unique),
            'payment_identity_hash' => hash('sha256', 'payment-identity-'.$unique),
            'provider' => 'duffel',
            'payment_type' => 'balance',
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'supplier_payment_id' => 'pay_secret_'.$unique,
            'resolved_at' => $status === FlightOrderPaymentAttempt::STATUS_PROCESSING ? null : now(),
        ]);

        if ($createdAt !== null) {
            FlightOrderPaymentAttempt::query()
                ->whereKey($payment->id)
                ->update(['created_at' => $createdAt]);
            $payment->refresh();
        }

        return $payment;
    }

    /** @return array<string, string> */
    private function agentAttributes(string $status, string $suffix): array
    {
        return [
            'name' => 'Agent '.$suffix,
            'email' => 'agent-'.$suffix.'@example.com',
            'status' => $status,
        ];
    }

    /** @return array<string, string> */
    private function affiliateAttributes(string $status, string $suffix): array
    {
        return [
            'name' => 'Affiliate '.$suffix,
            'email' => 'affiliate-'.$suffix.'@example.com',
            'referral_code' => 'REF-'.$suffix,
            'status' => $status,
        ];
    }

    /** @return array<string, string> */
    private function studentAttributes(string $status, string $suffix): array
    {
        return [
            'first_name' => 'Student',
            'last_name' => $suffix,
            'email' => 'student-'.$suffix.'@example.com',
            'reference_code' => 'STU-'.$suffix,
            'status' => $status,
        ];
    }

    /** @return array<string, string> */
    private function institutionAttributes(string $status, string $suffix): array
    {
        return [
            'name' => 'Institution '.$suffix,
            'email' => 'institution-'.$suffix.'@example.com',
            'status' => $status,
        ];
    }
}
