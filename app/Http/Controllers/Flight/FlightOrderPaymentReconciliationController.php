<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Services\Flight\FlightOrderPaymentReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class FlightOrderPaymentReconciliationController extends Controller
{
    public function __invoke(
        Request $request,
        string $attemptReference,
        FlightOrderPaymentReconciliationService $service,
    ): JsonResponse {
        $user =
            $request->user();

        if ($user === null) {
            return $this->notFound();
        }

        try {
            $result =
                $service->reconcile(
                    (int) $user->getAuthIdentifier(),
                    $attemptReference,
                );
        } catch (ServiceUnavailableHttpException) {
            return response()
                ->json(
                    [
                        'message' =>
                            'Flight payment reconciliation is temporarily unavailable.',
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

        if (! is_array($result)) {
            return $this->notFound();
        }

        $status =
            $result['status']
                ?? null;

        if (
            ! in_array(
                $status,
                [
                    'processing',
                    'succeeded',
                    'failed',
                ],
                true,
            )
        ) {
            return $this->notFound();
        }

        return response()
            ->json(
                [
                    'data' => [
                        'status' =>
                            $status,

                        'provider' =>
                            $result['provider'],
                    ],
                ],
                $status === 'processing'
                    ? 202
                    : 200,
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

    private function notFound(): JsonResponse
    {
        return response()
            ->json(
                [
                    'message' =>
                        'Flight payment attempt is unavailable.',
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