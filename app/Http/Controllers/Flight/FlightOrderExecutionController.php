<?php

namespace App\Http\Controllers\Flight;

use App\Exceptions\Flight\FlightOrderProcessingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\CreateFlightOrderExecutionRequest;
use App\Services\Flight\FlightOrderExecutionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderExecutionController extends Controller
{
    public function store(
        CreateFlightOrderExecutionRequest $request,
        FlightOrderExecutionService $executionService,
    ): JsonResponse {
        /*
         * Exposing the authenticated HTTP route does not automatically
         * enable order execution.
         *
         * This independent application-level gate remains false by
         * default. In particular, creating this controller does not
         * enable Duffel live order creation.
         */
        if (
            config(
                'flight_orders.http_execution_enabled',
                false,
            ) !== true
        ) {
            return $this->disabledResponse();
        }

        $validated =
            $request->validated();

        $userId =
            (int) $request
                ->user()
                ->getAuthIdentifier();

        try {
            $result =
                $executionService->execute(
                    $userId,
                    $validated[
                        'confirmation_intent_token'
                    ],
                );
        } catch (
            FlightOrderProcessingException $exception
        ) {
            /*
             * The confirmation intent has already been consumed and the
             * supplier accepted the create-order request. Processing is
             * not a completed order and is not safe to replay.
             */
            return $this->processingResponse(
                $exception->provider(),
                $exception->attemptReference(),
            );
        } catch (
            ServiceUnavailableHttpException
        ) {
            /*
             * The execution service consumes the intent before crossing
             * the provider boundary. A supplier-side failure can therefore
             * have an uncertain outcome and must not expose supplier details
             * or encourage blind replay.
             */
            return $this->unavailableResponse(
                true,
                null,
            );
        }

        $confirmationIntentConsumed =
            (
                $result[
                    'confirmation_intent_consumed'
                ]
                ?? false
            ) === true;

        if (! $confirmationIntentConsumed) {
            return $this->goneResponse();
        }

        $status =
            is_string(
                $result['status']
                    ?? null
            )
                ? trim(
                    $result['status']
                )
                : '';

        $provider =
            is_string(
                $result['provider']
                    ?? null
            )
                ? trim(
                    $result['provider']
                )
                : '';

        $liveOrderCreation =
            (
                $result[
                    'live_order_creation'
                ]
                ?? false
            ) === true;

        $orderCreated =
            (
                $result[
                    'order_created'
                ]
                ?? false
            ) === true;

        if (
            $status === 'created'
            && $provider !== ''
            && $liveOrderCreation
            && $orderCreated
        ) {
            return $this->createdResponse(
                $provider,
            );
        }

        return $this->unavailableResponse(
            true,
            $provider !== ''
                ? $provider
                : null,
        );
    }

    private function disabledResponse(): JsonResponse
    {
        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        'execution_disabled',

                    'live_order_creation' =>
                        false,

                    'order_created' =>
                        false,

                    'confirmation_intent_consumed' =>
                        false,
                ],

                'message' =>
                    'Flight order execution is currently unavailable.',
            ], 503),
        );
    }

    private function goneResponse(): JsonResponse
    {
        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        'confirmation_intent_unavailable',

                    'live_order_creation' =>
                        false,

                    'order_created' =>
                        false,

                    'confirmation_intent_consumed' =>
                        false,
                ],

                'message' =>
                    'This flight confirmation is no longer available. Please review the flight again.',
            ], 410),
        );
    }

    private function processingResponse(
        string $provider,
        ?string $attemptReference,
    ): JsonResponse {
        $data = [
            'status' =>
                'processing',

            'provider' =>
                $provider,

            'live_order_creation' =>
                true,

            'order_created' =>
                false,

            'confirmation_intent_consumed' =>
                true,
        ];

        if ($attemptReference !== null) {
            $data['attempt_reference'] =
                $attemptReference;
        }

        return $this->noStore(
            response()->json([
                'data' =>
                    $data,

                'message' =>
                    'Flight order creation is still processing. Do not retry this confirmation intent. Review or reconciliation is required before any further order attempt.',
            ], 202),
        );
    }
    private function createdResponse(
        string $provider,
    ): JsonResponse {
        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        'created',

                    'provider' =>
                        $provider,

                    'live_order_creation' =>
                        true,

                    'order_created' =>
                        true,

                    'confirmation_intent_consumed' =>
                        true,
                ],

                'message' =>
                    'Flight order creation completed.',
            ], 201),
        );
    }

    private function unavailableResponse(
        bool $confirmationIntentConsumed,
        ?string $provider,
    ): JsonResponse {
        return $this->noStore(
            response()->json([
                'data' => [
                    'status' =>
                        'unavailable',

                    'provider' =>
                        $provider,

                    'live_order_creation' =>
                        false,

                    'order_created' =>
                        false,

                    'confirmation_intent_consumed' =>
                        $confirmationIntentConsumed,
                ],

                'message' =>
                    'Flight order is temporarily unavailable. Please review the flight before any further attempt.',
            ], 503),
        );
    }

    private function noStore(
        JsonResponse $response,
    ): JsonResponse {
        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }
}