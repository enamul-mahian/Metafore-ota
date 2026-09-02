<?php

namespace Tests\Feature\Flight;

use App\Models\FlightOrderAttempt;
use App\Models\User;
use App\Services\Flight\FlightOrderAttemptRecordStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FlightOrderAttemptStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_attempt_can_be_marked_created_once(): void
    {
        Http::fake();

        $user =
            User::factory()->create();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_transition_created_1',
                );

        $this->assertIsArray(
            $created,
        );

        $resolved =
            $this->store()
                ->markCreated(
                    ' DUFFEL ',
                    ' off_transition_created_1 ',
                    ' supplier-order-neutral-1 ',
                );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $resolved,
        );

        $this->assertSame(
            FlightOrderAttempt::STATUS_CREATED,
            $resolved->status,
        );

        $this->assertSame(
            'supplier-order-neutral-1',
            $resolved->supplier_order_id,
        );

        $this->assertNotNull(
            $resolved->resolved_at,
        );

        $resolvedAt =
            $resolved
                ->resolved_at
                ->getTimestamp();

        $this->assertNull(
            $this->store()
                ->markCreated(
                    'duffel',
                    'off_transition_created_1',
                    'supplier-order-must-not-overwrite',
                ),
        );

        $this->assertNull(
            $this->store()
                ->markFailed(
                    'duffel',
                    'off_transition_created_1',
                ),
        );

        $fresh =
            FlightOrderAttempt::query()
                ->findOrFail(
                    $resolved->getKey(),
                );

        $this->assertSame(
            FlightOrderAttempt::STATUS_CREATED,
            $fresh->status,
        );

        $this->assertSame(
            'supplier-order-neutral-1',
            $fresh->supplier_order_id,
        );

        $this->assertSame(
            $resolvedAt,
            $fresh
                ->resolved_at
                ->getTimestamp(),
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_processing_attempt_can_be_marked_failed_once(): void
    {
        Http::fake();

        $user =
            User::factory()->create();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_transition_failed_1',
                );

        $this->assertIsArray(
            $created,
        );

        $resolved =
            $this->store()
                ->markFailed(
                    ' DUFFEL ',
                    ' off_transition_failed_1 ',
                );

        $this->assertInstanceOf(
            FlightOrderAttempt::class,
            $resolved,
        );

        $this->assertSame(
            FlightOrderAttempt::STATUS_FAILED,
            $resolved->status,
        );

        $this->assertNull(
            $resolved->supplier_order_id,
        );

        $this->assertNotNull(
            $resolved->resolved_at,
        );

        $resolvedAt =
            $resolved
                ->resolved_at
                ->getTimestamp();

        $this->assertNull(
            $this->store()
                ->markFailed(
                    'duffel',
                    'off_transition_failed_1',
                ),
        );

        $this->assertNull(
            $this->store()
                ->markCreated(
                    'duffel',
                    'off_transition_failed_1',
                    'supplier-order-must-not-appear',
                ),
        );

        $fresh =
            FlightOrderAttempt::query()
                ->findOrFail(
                    $resolved->getKey(),
                );

        $this->assertSame(
            FlightOrderAttempt::STATUS_FAILED,
            $fresh->status,
        );

        $this->assertNull(
            $fresh->supplier_order_id,
        );

        $this->assertSame(
            $resolvedAt,
            $fresh
                ->resolved_at
                ->getTimestamp(),
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_invalid_and_unknown_transition_identity_fail_closed(): void
    {
        Http::fake();

        $user =
            User::factory()->create();

        $created =
            $this->store()
                ->createProcessing(
                    (int) $user->getKey(),
                    'duffel',
                    'off_transition_guard_1',
                );

        $this->assertIsArray(
            $created,
        );

        $store =
            $this->store();

        $this->assertNull(
            $store->markCreated(
                'Duffel Invalid!',
                'off_transition_guard_1',
                'supplier-order-1',
            ),
        );

        $this->assertNull(
            $store->markCreated(
                'duffel',
                '',
                'supplier-order-1',
            ),
        );

        $this->assertNull(
            $store->markCreated(
                'duffel',
                'off_transition_guard_1',
                '',
            ),
        );

        $this->assertNull(
            $store->markCreated(
                'duffel',
                'off_transition_guard_1',
                "bad\nsupplier-order",
            ),
        );

        $this->assertNull(
            $store->markCreated(
                'duffel',
                'off_transition_guard_1',
                str_repeat(
                    'x',
                    256,
                ),
            ),
        );

        $this->assertNull(
            $store->markFailed(
                'Duffel Invalid!',
                'off_transition_guard_1',
            ),
        );

        $this->assertNull(
            $store->markFailed(
                'duffel',
                '',
            ),
        );

        $this->assertNull(
            $store->markCreated(
                'duffel',
                'off_transition_missing',
                'supplier-order-unknown',
            ),
        );

        $this->assertNull(
            $store->markFailed(
                'duffel',
                'off_transition_missing',
            ),
        );

        /** @var FlightOrderAttempt $attempt */
        $attempt =
            $created['attempt'];

        $attempt->refresh();

        $this->assertSame(
            FlightOrderAttempt::STATUS_PROCESSING,
            $attempt->status,
        );

        $this->assertNull(
            $attempt->supplier_order_id,
        );

        $this->assertNull(
            $attempt->resolved_at,
        );

        $this->assertDatabaseCount(
            'flight_order_attempts',
            1,
        );

        Http::assertNothingSent();
    }

    public function test_transition_primitives_remain_local_only_and_status_http_stays_read_only(): void
    {
        $store =
            file_get_contents(
                app_path(
                    'Services/Flight/FlightOrderAttemptRecordStore.php',
                ),
            );

        $statusController =
            file_get_contents(
                app_path(
                    'Http/Controllers/Flight/FlightOrderAttemptStatusController.php',
                ),
            );

        $ui =
            file_get_contents(
                resource_path(
                    'js/app.js',
                ),
            );

        $this->assertIsString(
            $store,
        );

        $this->assertIsString(
            $statusController,
        );

        $this->assertIsString(
            $ui,
        );

        foreach ([
            'public function markCreated(',
            'public function markFailed(',
            'FlightOrderAttempt::STATUS_PROCESSING',
            'FlightOrderAttempt::STATUS_CREATED',
            'FlightOrderAttempt::STATUS_FAILED',
            "'supplier_order_id'",
            "'resolved_at'",
        ] as $requiredSignal) {
            $this->assertStringContainsString(
                $requiredSignal,
                $store,
            );
        }

        foreach ([
            'Http::',
            '->post(',
            '->get(',
            '/air/orders',
            'Duffel',
            'ShouldQueue',
            'dispatch(',
            'Bus::',
            'Queue::',
        ] as $forbiddenBehavior) {
            $this->assertStringNotContainsString(
                $forbiddenBehavior,
                $store,
            );
        }

        $this->assertStringContainsString(
            'findForUser(',
            $statusController,
        );

        $this->assertStringNotContainsString(
            'markCreated(',
            $statusController,
        );

        $this->assertStringNotContainsString(
            'markFailed(',
            $statusController,
        );

        $this->assertStringNotContainsString(
            '/air/orders',
            $statusController,
        );

        $this->assertStringNotContainsString(
            '/flights/bookings/orders/attempts/',
            $ui,
        );
    }

    private function store(): FlightOrderAttemptRecordStore
    {
        return app(
            FlightOrderAttemptRecordStore::class,
        );
    }
}