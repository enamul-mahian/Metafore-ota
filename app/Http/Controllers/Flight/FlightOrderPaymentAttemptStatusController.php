<?php

namespace App\Http\Controllers\Flight;

use App\Http\Controllers\Controller;
use App\Models\FlightOrderPaymentAttempt;
use App\Services\Flight\FlightOrderPaymentAttemptRecordStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FlightOrderPaymentAttemptStatusController extends Controller
{
    public function __invoke(
        Request $request,
        string $attemptReference,
        FlightOrderPaymentAttemptRecordStore $store,
    ): JsonResponse {
        $user =
            $request->user();

        if ($user === null) {
            return $this->notFound();
        }

        $attempt =
            $store->findForUser(
                (int) $user->getAuthIdentifier(),
                $attemptReference,
            );

        if (
            ! $attempt
                instanceof FlightOrderPaymentAttempt
        ) {
            return $this->notFound();
        }

        return response()
            ->json([
                'data' => [
                    'status' =>
                        $attempt->status,

                    'provider' =>
                        $attempt->provider,
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