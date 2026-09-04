<?php

namespace Tests\Feature\Admin;

use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BookingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_bookings(): void
    {
        $this->get(route('admin.bookings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_is_forbidden_from_admin_bookings(): void
    {
        $customer = $this->user('customer');
        $booking = $this->createBooking($customer);

        $this->actingAs($customer)
            ->get(route('admin.bookings.index'))
            ->assertForbidden();
        $this->actingAs($customer)
            ->get(route('admin.bookings.show', $booking))
            ->assertForbidden();
    }

    public function test_admin_and_super_admin_can_view_booking_index(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Bookings')
            ->assertSee('Read-only operational view');

        $this->actingAs($this->user('super-admin'))
            ->get(route('admin.bookings.index'))
            ->assertOk();
    }

    public function test_admin_sees_records_and_correct_customers_across_accounts(): void
    {
        $firstCustomer = $this->user('customer', [
            'name' => 'First Customer',
            'email' => 'first@example.com',
        ]);
        $secondCustomer = $this->user('customer', [
            'name' => 'Second Customer',
            'email' => 'second@example.com',
        ]);
        $firstBooking = $this->createBooking($firstCustomer, [
            'status' => FlightOrderAttempt::STATUS_CREATED,
        ]);
        $secondBooking = $this->createBooking($secondCustomer, [
            'status' => FlightOrderAttempt::STATUS_FAILED,
        ]);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('#'.$firstBooking->id)
            ->assertSee('First Customer')
            ->assertSee('first@example.com')
            ->assertSee('#'.$secondBooking->id)
            ->assertSee('Second Customer')
            ->assertSee('second@example.com');
    }

    public function test_admin_can_view_safe_booking_and_payment_details(): void
    {
        $customer = $this->user('customer', [
            'name' => 'Booking Customer',
            'email' => 'booking@example.com',
        ]);
        $booking = $this->createBooking($customer, [
            'status' => FlightOrderAttempt::STATUS_CREATED,
            'resolved_at' => now(),
        ]);
        $payment = $this->createPayment($customer, $booking, [
            'status' => FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
            'amount' => '325.50',
            'currency' => 'USD',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.show', $booking));

        $response
            ->assertOk()
            ->assertSee('Booking #'.$booking->id)
            ->assertSee('Booking Customer')
            ->assertSee('booking@example.com')
            ->assertSee('Created')
            ->assertSee('Succeeded')
            ->assertSee('USD 325.50')
            ->assertDontSee($booking->reference_hash)
            ->assertDontSee($booking->attempt_identity_hash)
            ->assertDontSee($payment->reference_hash)
            ->assertDontSee($payment->payment_identity_hash)
            ->assertDontSee($booking->supplier_offer_id)
            ->assertDontSee($booking->supplier_order_id)
            ->assertDontSee($payment->supplier_payment_id);
    }

    public function test_hidden_identifiers_are_not_rendered_on_index(): void
    {
        $customer = $this->user('customer');
        $booking = $this->createBooking($customer);
        $payment = $this->createPayment($customer, $booking);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertDontSee($booking->reference_hash)
            ->assertDontSee($booking->attempt_identity_hash)
            ->assertDontSee($payment->reference_hash)
            ->assertDontSee($payment->payment_identity_hash)
            ->assertDontSee($booking->supplier_offer_id)
            ->assertDontSee($booking->supplier_order_id)
            ->assertDontSee($payment->supplier_payment_id);
    }

    public function test_missing_admin_booking_returns_not_found(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.show', 999999))
            ->assertNotFound();
    }

    public function test_admin_bookings_are_paginated(): void
    {
        $customer = $this->user('customer');

        foreach (range(1, 21) as $number) {
            $this->createBooking($customer, [
                'provider' => 'provider-'.$number,
            ]);
        }

        $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee('Provider-21')
            ->assertDontSee('Provider-1</td>', false)
            ->assertSee('page=2', false);

        $this->actingAs($this->user('admin'))
            ->get(route('admin.bookings.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Provider-1');
    }

    public function test_no_admin_booking_mutation_route_exists(): void
    {
        $this->assertFalse(Route::has('admin.bookings.store'));
        $this->assertFalse(Route::has('admin.bookings.update'));
        $this->assertFalse(Route::has('admin.bookings.destroy'));

        $this->actingAs($this->user('admin'))
            ->post(route('admin.bookings.index'))
            ->assertMethodNotAllowed();
    }

    public function test_admin_navigation_points_to_bookings(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertSee(route('admin.bookings.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.settings.manage'))
            ->assertOk()
            ->assertSee(route('admin.bookings.index'), false);
    }

    /** @param array<string, mixed> $attributes */
    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $attributes */
    private function createBooking(
        User $user,
        array $attributes = []
    ): FlightOrderAttempt {
        $unique = uniqid('', true);

        return FlightOrderAttempt::query()->create(array_merge([
            'user_id' => $user->id,
            'reference_hash' => hash('sha256', 'reference-'.$unique),
            'attempt_identity_hash' => hash('sha256', 'identity-'.$unique),
            'provider' => 'duffel',
            'supplier_offer_id' => 'off_secret_'.$unique,
            'status' => FlightOrderAttempt::STATUS_PROCESSING,
            'supplier_order_id' => 'ord_secret_'.$unique,
        ], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function createPayment(
        User $user,
        FlightOrderAttempt $booking,
        array $attributes = []
    ): FlightOrderPaymentAttempt {
        $unique = uniqid('', true);

        return FlightOrderPaymentAttempt::query()->create(array_merge([
            'user_id' => $user->id,
            'flight_order_attempt_id' => $booking->id,
            'reference_hash' => hash('sha256', 'payment-reference-'.$unique),
            'payment_identity_hash' => hash('sha256', 'payment-identity-'.$unique),
            'provider' => 'duffel',
            'payment_type' => 'balance',
            'amount' => '200.00',
            'currency' => 'BDT',
            'status' => FlightOrderPaymentAttempt::STATUS_PROCESSING,
            'supplier_payment_id' => 'pay_secret_'.$unique,
        ], $attributes));
    }
}
