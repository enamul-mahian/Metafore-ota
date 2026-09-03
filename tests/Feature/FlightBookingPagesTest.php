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
