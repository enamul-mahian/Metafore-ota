<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Services\Flight\FlightOrderPaymentReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderPaymentReadinessController extends Controller
{
    public function __invoke(
        Request $request,
        string $attemptReference,
        FlightOrderPaymentReadinessService $readinessService,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            return $this->unavailableResponse();
        }

        $userId =
            (int) $user->getAuthIdentifier();

        try {
            $readiness =
                $readinessService->read(
                    $userId,
                    $attemptReference,
                );
        } catch (ServiceUnavailableHttpException) {
            return response()
                ->json(
                    [
                        'message' =>
                            'Flight order payment readiness is temporarily unavailable.',
                    ],
                    503,
                )
                ->header(
                    'Cache-Control',
                    'private, no-store, max-age=0',
                )
                ->header(
                    'Pragma',
                    'no-cache',
                );
        }

        if (! is_array($readiness)) {
            return $this->unavailableResponse();
        }

        return response()
            ->json([
                'data' => [
                    'status' =>
                        $readiness['status'],

                    'provider' =>
                        $readiness['provider'],

                    'awaiting_payment' =>
                        $readiness['awaiting_payment'],

                    'total_amount' =>
                        $readiness['total_amount'],

                    'total_currency' =>
                        $readiness['total_currency'],

                    'payment_required_by' =>
                        $readiness['payment_required_by'],
                ],
            ])
            ->header(
                'Cache-Control',
                'private, no-store, max-age=0',
            )
            ->header(
                'Pragma',
                'no-cache',
            );
    }

    private function unavailableResponse(): JsonResponse
    {
        return response()
            ->json(
                [
                    'message' =>
                        'Flight order payment readiness is unavailable.',
                ],
                404,
            )
            ->header(
                'Cache-Control',
                'private, no-store, max-age=0',
            )
            ->header(
                'Pragma',
                'no-cache',
            );
    }
}