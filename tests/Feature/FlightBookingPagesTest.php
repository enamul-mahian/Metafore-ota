<?php

namespace Tests\Feature;

use App\Models\FlightOrderAttempt;
use App\Models\FlightOrderPaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FlightBookingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_bookings(): void
    {
        $this->get(route('bookings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_verified_user_without_booking_permission_is_forbidden(): void
    {
        $user =
            User::factory()->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertForbidden();
    }

    public function test_customer_can_view_empty_booking_state(): void
    {
        $user =
            $this->bookingUser();

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('My Bookings')
            ->assertSee('No flight bookings yet');
    }

    public function test_customer_sees_only_their_own_booking_list_records(): void
    {
        $user =
            $this->bookingUser();

        $otherUser =
            $this->bookingUser([
                'email' => 'other-customer@example.com',
            ]);

        $ownBooking =
            $this->createBookingForUser(
                $user,
                [
                    'status' => FlightOrderAttempt::STATUS_CREATED,
                    'supplier_order_id' => 'ord_customer_private_123',
                ],
            );

        $this->createPaymentForBooking(
            $user,
            $ownBooking,
            [
                'status' => FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
                'amount' => '125.50',
                'currency' => 'USD',
                'supplier_payment_id' => 'pay_customer_private_123',
            ],
        );

        $otherBooking =
            $this->createBookingForUser(
                $otherUser,
                [
                    'status' => FlightOrderAttempt::STATUS_FAILED,
                    'supplier_order_id' => 'ord_other_private_456',
                ],
            );

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('Booking #'.$ownBooking->id)
            ->assertSee('Order Created')
            ->assertSee('Payment Succeeded')
            ->assertSee('USD 125.50')
            ->assertDontSee('Booking #'.$otherBooking->id)
            ->assertDontSee('ord_customer_private_123')
            ->assertDontSee('pay_customer_private_123');
    }

    public function test_customer_booking_history_is_paginated_in_deterministic_latest_order(): void
    {
        $user =
            $this->bookingUser();

        $oldestBooking =
            $this->createBookingForUser($user);

        for ($bookingNumber = 0; $bookingNumber < 9; $bookingNumber++) {
            $this->createBookingForUser($user);
        }

        $newestBooking =
            $this->createBookingForUser($user);

        $firstPage = $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('Booking #'.$newestBooking->id)
            ->assertSee('Showing 1&ndash;10', false)
            ->assertSee('of 11 bookings')
            ->assertSee('Page 1 of 2')
            ->assertSee(
                route('bookings.index', ['page' => 2]),
                false,
            );

        $firstPage->assertViewHas(
            'bookings',
            fn ($bookings): bool => $bookings->count() === 10
                && $bookings->first()->is($newestBooking)
                && ! $bookings->contains($oldestBooking),
        );

        $secondPage = $this->actingAs($user)
            ->get(route('bookings.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Booking #'.$oldestBooking->id)
            ->assertSee('Showing 11&ndash;11', false)
            ->assertSee('of 11 bookings')
            ->assertSee('Page 2 of 2');

        $secondPage->assertViewHas(
            'bookings',
            fn ($bookings): bool => $bookings->count() === 1
                && $bookings->first()->is($oldestBooking)
                && ! $bookings->contains($newestBooking),
        );
    }

    public function test_customer_can_view_owned_booking_details_without_supplier_identifiers(): void
    {
        $user =
            $this->bookingUser();

        $booking =
            $this->createBookingForUser(
                $user,
                [
                    'status' => FlightOrderAttempt::STATUS_CREATED,
                    'supplier_order_id' => 'ord_hidden_from_customer',
                    'resolved_at' => now(),
                ],
            );

        $this->createPaymentForBooking(
            $user,
            $booking,
            [
                'status' => FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
                'amount' => '499.00',
                'currency' => 'GBP',
                'supplier_payment_id' => 'pay_hidden_from_customer',
                'resolved_at' => now(),
            ],
        );

        $this->actingAs($user)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Booking #'.$booking->id)
            ->assertSee('Customer booking confirmation')
            ->assertSee('GBP 499.00')
            ->assertSee('airline-issued e-ticket')
            ->assertSee(route('bookings.invoice', $booking), false)
            ->assertDontSee('ord_hidden_from_customer')
            ->assertDontSee('pay_hidden_from_customer')
            ->assertDontSee($booking->reference_hash)
            ->assertDontSee($booking->attempt_identity_hash);
    }

    public function test_customer_cannot_view_another_users_booking_details(): void
    {
        $user =
            $this->bookingUser();

        $otherUser =
            $this->bookingUser([
                'email' => 'another-customer@example.com',
            ]);

        $otherBooking =
            $this->createBookingForUser(
                $otherUser,
            );

        $this->actingAs($user)
            ->get(route('bookings.show', $otherBooking))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_from_booking_invoice(): void
    {
        $booking =
            $this->createBookingForUser(
                User::factory()->create(),
            );

        $this->get(route('bookings.invoice', $booking))
            ->assertRedirect(route('login'));
    }

    public function test_verified_user_without_booking_permission_cannot_view_invoice(): void
    {
        $owner =
            User::factory()->create([
                'email_verified_at' => now(),
            ]);

        $booking =
            $this->createBookingForUser($owner);

        $this->actingAs($owner)
            ->get(route('bookings.invoice', $booking))
            ->assertForbidden();
    }

    public function test_customer_can_view_owned_payment_record_without_supplier_identifiers(): void
    {
        $user =
            $this->bookingUser();

        $booking =
            $this->createBookingForUser(
                $user,
                [
                    'status' => FlightOrderAttempt::STATUS_CREATED,
                    'supplier_order_id' => 'ord_never_render_invoice',
                    'resolved_at' => now(),
                ],
            );

        $payment =
            $this->createPaymentForBooking(
                $user,
                $booking,
                [
                    'status' => FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
                    'amount' => '725.40',
                    'currency' => 'EUR',
                    'supplier_payment_id' => 'pay_never_render_invoice',
                    'resolved_at' => now(),
                ],
            );

        $this->actingAs($user)
            ->get(route('bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Booking invoice / payment record')
            ->assertSee('Internal booking #'.$booking->id)
            ->assertSee('Payment Record')
            ->assertSee('#'.$payment->id)
            ->assertSee('Payment Succeeded')
            ->assertSee('Total Paid')
            ->assertSee('EUR 725.40')
            ->assertSee('not an airline-issued e-ticket')
            ->assertDontSee('ord_never_render_invoice')
            ->assertDontSee('pay_never_render_invoice')
            ->assertDontSee($booking->reference_hash)
            ->assertDontSee($booking->attempt_identity_hash)
            ->assertDontSee($payment->reference_hash)
            ->assertDontSee($payment->payment_identity_hash)
            ->assertDontSee('Booking Reference')
            ->assertDontSee('Ticket Number');
    }

    public function test_booking_invoice_reports_when_no_payment_is_stored(): void
    {
        $user =
            $this->bookingUser();

        $booking =
            $this->createBookingForUser($user);

        $this->actingAs($user)
            ->get(route('bookings.invoice', $booking))
            ->assertOk()
            ->assertSee('Payment Not Started')
            ->assertSee('No payment attempt is stored for this booking')
            ->assertSee('invoice amount is available')
            ->assertDontSee('Total Paid');
    }

    public function test_customer_cannot_view_another_users_booking_invoice(): void
    {
        $user =
            $this->bookingUser();

        $otherUser =
            $this->bookingUser([
                'email' => 'invoice-owner@example.com',
            ]);

        $otherBooking =
            $this->createBookingForUser($otherUser);

        $this->actingAs($user)
            ->get(route('bookings.invoice', $otherBooking))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function bookingUser(array $attributes = []): User
    {
        Permission::findOrCreate(
            'flights.book',
            'web',
        );

        $user =
            User::factory()->create(
                array_merge(
                    [
                        'email_verified_at' => now(),
                    ],
                    $attributes,
                ),
            );

        $user->givePermissionTo(
            'flights.book',
        );

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createBookingForUser(
        User $user,
        array $attributes = [],
    ): FlightOrderAttempt {
        return FlightOrderAttempt::query()->create(
            array_merge(
                [
                    'user_id' => $user->id,
                    'reference_hash' => hash(
                        'sha256',
                        'reference-'.$user->id.'-'.uniqid('', true),
                    ),
                    'attempt_identity_hash' => hash(
                        'sha256',
                        'identity-'.$user->id.'-'.uniqid('', true),
                    ),
                    'provider' => 'duffel',
                    'supplier_offer_id' => 'off_hidden_from_customer',
                    'status' => FlightOrderAttempt::STATUS_PROCESSING,
                ],
                $attributes,
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPaymentForBooking(
        User $user,
        FlightOrderAttempt $booking,
        array $attributes = [],
    ): FlightOrderPaymentAttempt {
        return FlightOrderPaymentAttempt::query()->create(
            array_merge(
                [
                    'user_id' => $user->id,
                    'flight_order_attempt_id' => $booking->id,
                    'reference_hash' => hash(
                        'sha256',
                        'payment-reference-'.$user->id.'-'.uniqid('', true),
                    ),
                    'payment_identity_hash' => hash(
                        'sha256',
                        'payment-identity-'.$user->id.'-'.uniqid('', true),
                    ),
                    'provider' => 'duffel',
                    'payment_type' => 'balance',
                    'amount' => '200.00',
                    'currency' => 'USD',
                    'status' => FlightOrderPaymentAttempt::STATUS_PROCESSING,
                ],
                $attributes,
            ),
        );
    }
}
