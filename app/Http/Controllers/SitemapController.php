<?php

namespace App\Http\Controllers;

use App\Services\Feature\FeatureManager;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    /**
     * Public pages that exist and are visible to guests.
     */
    public function __invoke(FeatureManager $features): Response
    {
        $featureForRoute = [
            'flights.index' => 'flights',
            'hotels.index' => 'hotels',
            'tours.index' => 'tours',
            'visa.index' => 'visa',
            'work-visa.index' => 'work-visa',
            'about' => 'about',
            'support' => 'support',
        ];

        $urls = collect(array_keys(config('site.seo_pages', [])))
            ->filter(fn (string $routeName): bool => Route::has($routeName))
            ->filter(fn (string $routeName): bool => ! isset($featureForRoute[$routeName])
                || ! $features->isRegistered($featureForRoute[$routeName])
                || $features->isVisibleTo($featureForRoute[$routeName], null))
            ->filter(fn (string $routeName): bool => $this->isPublic($routeName))
            ->map(fn (string $routeName): string => route($routeName))
            ->values();

        return response()
            ->view('sitemap', ['urls' => $urls, 'lastModified' => now()->toDateString()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function isPublic(string $routeName): bool
    {
        $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];

        return ! in_array('auth', $middleware, true);
    }
}
