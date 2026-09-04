<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Feature\FeatureManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureIsVisible
{
    public function __construct(
        private readonly FeatureManager $features,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$featureKeys,
    ): Response {
        if ($featureKeys === []) {
            $featureKeys = [''];
        }

        $user = $request->user();
        $viewer = $user instanceof User ? $user : null;

        foreach ($featureKeys as $featureKey) {
            if ($this->features->isVisibleTo($featureKey, $viewer)) {
                continue;
            }

            $message = $this->features->unavailableMessage($featureKey);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->view(
                'errors.feature-unavailable',
                ['message' => $message],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $next($request);
    }
}
