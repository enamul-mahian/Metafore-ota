<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flight\ReviewFlightBookingDraftRequest;
use App\Services\Flight\FlightBookingDraftStore;
use App\Services\Flight\FlightOfferRevalidationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightBookingDraftReviewController extends Controller
{
    public function store(
        ReviewFlightBookingDraftRequest $request,
        FlightBookingDraftStore $bookingDraftStore,
        FlightOfferRevalidationService $revalidationService,
    ): JsonResponse {
        $bookingDraftToken = (string) $request->validated(
            'booking_draft_token',
        );

        $userId = (int) $request->user()
            ->getAuthIdentifier();

        $draft = $bookingDraftStore->get(
            $userId,
            $bookingDraftToken,
        );

        if ($draft === null) {
            return $this->goneResponse();
        }

        $criteria =
            $draft['criteria']
            ?? null;

        $trustedOffer =
            $draft['offer']
            ?? null;

        $travelers =
            $draft['travelers']
            ?? null;

        if (
            ! is_array($criteria)
            || ! is_array($trustedOffer)
            || ! is_array($travelers)
        ) {
            return $this->goneResponse();
        }

        /*
         * Revalidation receives only the offer recovered from the encrypted,
         * customer-scoped booking draft. No client-supplied fare, carrier,
         * route, currency, or supplier offer ID is accepted here.
         *
         * Fixture revalidation remains demo-only and sends no HTTP request.
         * Duffel revalidation uses its dedicated GET-only adapter.
         */
        $revalidation = $revalidationService->revalidate(
            $trustedOffer,
        );

        $reviewOffer =
            $revalidation['offer']
            ?? null;

        if (! is_array($reviewOffer)) {
            throw new ServiceUnavailableHttpException(
                60,
                'Flight offer revalidation is temporarily unavailable.',
            );
        }

        $response = response()->json([
            'data' => [
                'status' => 'draft_review',

                'traveler_count' => count(
                    $travelers,
                ),

                'criteria' => $this->criteriaForReview(
                    $criteria,
                ),

                /*
                 * The review uses the provider-neutral revalidation result.
                 * For Duffel this means the latest supplier fare/currency and
                 * carrier summary for the same trusted supplier offer ID.
                 */
                'offer' => $this->offerForReview(
                    $reviewOffer,
                ),

                'revalidation' => $this->revalidationForReview(
                    $revalidation,
                ),

                'created_at' =>
                    $draft['created_at']
                    ?? null,

                'expires_in_seconds' =>
                    $bookingDraftStore->expiresInSeconds(),
            ],
        ]);

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    private function criteriaForReview(
        array $criteria,
    ): array {
        return [
            'trip_type' =>
                $criteria['trip_type']
                ?? null,

            'origin' =>
                $criteria['origin']
                ?? null,

            'destination' =>
                $criteria['destination']
                ?? null,

            'departure_date' =>
                $criteria['departure_date']
                ?? null,

            'return_date' =>
                $criteria['return_date']
                ?? null,

            'adults' =>
                $criteria['adults']
                ?? null,

            'children' =>
                $criteria['children']
                ?? null,

            'infants' =>
                $criteria['infants']
                ?? null,

            'cabin_class' =>
                $criteria['cabin_class']
                ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $offer
     * @return array<string, mixed>
     */
    private function offerForReview(
        array $offer,
    ): array {
        $owner =
            $offer['owner']
            ?? null;

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
                    is_array($owner)
                        ? ($owner['code'] ?? null)
                        : null,

                'name' =>
                    is_array($owner)
                        ? ($owner['name'] ?? null)
                        : null,
            ],

            /*
             * The Duffel adapter deliberately preserves the trusted normalized
             * route/slices from the original server-side selection.
             */
            'origin' =>
                $offer['origin']
                ?? null,

            'destination' =>
                $offer['destination']
                ?? null,
        ];
    }

    /**
     * Expose only review-safe revalidation metadata.
     *
     * No supplier payload, traveler PII, token, order, payment, or ticket
     * object is returned through this response.
     *
     * @param array<string, mixed> $revalidation
     * @return array<string, mixed>
     */
    private function revalidationForReview(
        array $revalidation,
    ): array {
        return [
            'status' =>
                $revalidation['status']
                ?? null,

            'provider' =>
                $revalidation['provider']
                ?? null,

            'live_revalidation' => (bool) (
                $revalidation['live_revalidation']
                ?? false
            ),

            'price_changed' => (bool) (
                $revalidation['price_changed']
                ?? false
            ),
        ];
    }

    private function goneResponse(): JsonResponse
    {
        $response = response()->json(
            [
                'message' =>
                    'Booking draft is no longer available. Please create a new draft.',
            ],
            410,
        );

        $response->headers->set(
            'Cache-Control',
            'no-store, private',
        );

        return $response;
    }
}
