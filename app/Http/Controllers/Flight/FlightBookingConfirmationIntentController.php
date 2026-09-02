<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\CreateFlightBookingConfirmationIntentRequest;
use App\Services\Flight\FlightBookingConfirmationIntentStore;
use App\Services\Flight\FlightBookingDraftStore;
use App\Services\Flight\FlightOfferRevalidationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightBookingConfirmationIntentController extends Controller
{
    public function store(
        CreateFlightBookingConfirmationIntentRequest $request,
        FlightBookingDraftStore $bookingDraftStore,
        FlightOfferRevalidationService $revalidationService,
        FlightBookingConfirmationIntentStore $confirmationIntentStore,
    ): JsonResponse {
        $validated =
            $request->validated();

        $userId =
            (int) $request
                ->user()
                ->getAuthIdentifier();

        $draft =
            $bookingDraftStore->get(
                $userId,
                $validated['booking_draft_token'],
            );

        if (
            ! is_array($draft)
            || ! isset(
                $draft['criteria'],
                $draft['offer'],
                $draft['travelers'],
            )
            || ! is_array($draft['criteria'])
            || ! is_array($draft['offer'])
            || ! is_array($draft['travelers'])
        ) {
            return $this->goneResponse();
        }

        $criteria =
            $draft['criteria'];

        $trustedOffer =
            $draft['offer'];

        $travelers =
            $draft['travelers'];

        /*
         * The client acknowledgement is never used to construct,
         * replace, or mutate trusted fare data.
         *
         * The trusted draft offer is always revalidated again here.
         */
        $revalidation =
            $revalidationService->revalidate(
                $trustedOffer,
            );

        $currentOffer =
            $revalidation['offer']
                ?? null;

        if (! is_array($currentOffer)) {
            throw new ServiceUnavailableHttpException(
                null,
                'Flight confirmation is temporarily unavailable.',
            );
        }

        $revalidationStatus =
            $revalidation['status']
                ?? null;

        $revalidationProvider =
            $revalidation['provider']
                ?? null;

        $liveRevalidation =
            $revalidation['live_revalidation']
                ?? false;

        if (
            $revalidationStatus !== 'revalidated'
            || $liveRevalidation !== true
        ) {
            return $this->liveRevalidationRequiredResponse(
                $revalidationProvider,
                $liveRevalidation,
            );
        }

        $currentAmount =
            $currentOffer['total_amount']
                ?? null;

        $currentCurrency =
            $currentOffer['currency']
                ?? null;

        if (
            ! is_string($currentAmount)
            || $currentAmount === ''
            || ! is_string($currentCurrency)
            || $currentCurrency === ''
        ) {
            throw new ServiceUnavailableHttpException(
                null,
                'Flight confirmation is temporarily unavailable.',
            );
        }

        $acknowledgedAmount =
            $validated[
                'acknowledged_total_amount'
            ];

        $acknowledgedCurrency =
            $validated[
                'acknowledged_currency'
            ];

        /*
         * Client values are acknowledgement-only concurrency values.
         * They never become the trusted fare.
         */
        if (
            ! hash_equals(
                $currentAmount,
                $acknowledgedAmount,
            )
            || ! hash_equals(
                $currentCurrency,
                $acknowledgedCurrency,
            )
        ) {
            return $this->fareChangedResponse(
                $currentOffer,
                $revalidation,
            );
        }

        $safeRevalidation =
            $this->revalidationForResponse(
                $revalidation,
            );

        /*
         * Store only the server-trusted revalidated snapshot.
         * The client acknowledgement values are not persisted as fare.
         */
        $confirmationIntentToken =
            $confirmationIntentStore->put(
                $userId,
                $criteria,
                $currentOffer,
                $travelers,
                $safeRevalidation,
            );

        $response =
            response()->json([
                'data' => [
                    'status' =>
                        'confirmation_intent',

                    'confirmation_intent_token' =>
                        $confirmationIntentToken,

                    'traveler_count' =>
                        count($travelers),

                    'offer' =>
                        $this->offerForResponse(
                            $currentOffer,
                        ),

                    'revalidation' =>
                        $safeRevalidation,

                    'expires_in_seconds' =>
                        $confirmationIntentStore
                            ->expiresInSeconds(),
                ],
            ], 201);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    private function goneResponse(): JsonResponse
    {
        $response =
            response()->json([
                'message' =>
                    'This booking draft is no longer available. Please search again.',
            ], 410);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    private function liveRevalidationRequiredResponse(
        mixed $provider,
        mixed $liveRevalidation,
    ): JsonResponse {
        $response =
            response()->json([
                'data' => [
                    'status' =>
                        'live_revalidation_required',

                    'provider' =>
                        is_string($provider)
                            ? $provider
                            : null,

                    'live_revalidation' =>
                        $liveRevalidation === true,

                    'confirmation_intent_created' =>
                        false,
                ],

                'message' =>
                    'A live supplier fare revalidation is required before confirmation.',
            ], 409);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $offer
     * @param array<string, mixed> $revalidation
     */
    private function fareChangedResponse(
        array $offer,
        array $revalidation,
    ): JsonResponse {
        $response =
            response()->json([
                'data' => [
                    'status' =>
                        'fare_changed',

                    'requires_review' =>
                        true,

                    'confirmation_intent_created' =>
                        false,

                    'offer' =>
                        $this->offerForResponse(
                            $offer,
                        ),

                    'revalidation' =>
                        $this->revalidationForResponse(
                            $revalidation,
                        ),
                ],

                'message' =>
                    'The fare changed. Please review the latest trusted fare before confirming.',
            ], 409);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    private function offerForResponse(
        array $offer,
    ): array {
        return [
            'id' =>
                $offer['id']
                    ?? null,

            'provider' =>
                $offer['provider']
                    ?? null,

            'total_amount' =>
                $offer['total_amount']
                    ?? null,

            'currency' =>
                $offer['currency']
                    ?? null,

            'owner' => [
                'code' =>
                    data_get(
                        $offer,
                        'owner.code',
                    ),

                'name' =>
                    data_get(
                        $offer,
                        'owner.name',
                    ),
            ],

            'origin' =>
                $offer['origin']
                    ?? null,

            'destination' =>
                $offer['destination']
                    ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $revalidation
     * @return array<string, mixed>
     */
    private function revalidationForResponse(
        array $revalidation,
    ): array {
        return [
            'status' =>
                is_string(
                    $revalidation['status']
                        ?? null
                )
                    ? $revalidation['status']
                    : null,

            'provider' =>
                is_string(
                    $revalidation['provider']
                        ?? null
                )
                    ? $revalidation['provider']
                    : null,

            'live_revalidation' =>
                (
                    $revalidation[
                        'live_revalidation'
                    ]
                    ?? false
                ) === true,

            'price_changed' =>
                (
                    $revalidation[
                        'price_changed'
                    ]
                    ?? false
                ) === true,
        ];
    }
}
