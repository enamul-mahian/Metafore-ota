<?php

namespace App\View\Composers;

use App\Models\User;
use App\Services\Feature\FeatureManager;
use App\Services\SiteContentService;
use App\Services\Travel\TravelServiceRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Builds the public header/footer navigation, respecting feature
 * visibility, provider availability and the viewer's permissions.
 */
class SiteLayoutComposer
{
    public function __construct(
        private readonly Request $request,
        private readonly FeatureManager $features,
        private readonly TravelServiceRegistry $travelServices,
        private readonly SiteContentService $site,
    ) {}

    public function compose(View $view): void
    {
        $view->with([
            'site' => $this->site,
            'siteNavigation' => $this->navigation(),
            'siteContact' => $this->site->contact(),
            'siteSeo' => $this->site->seoFor($this->request->route()?->getName()),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, icon: string, href: string, active: bool, badge: string|null}>
     */
    private function navigation(): array
    {
        $user = Auth::user();
        $user = $user instanceof User ? $user : null;
        $services = $this->travelServices->all();
        $items = [];

        if ($this->features->isVisibleTo('flights', $user)) {
            // Guests may search flights; signing in is only needed to book.
            $items[] = $this->item('flights', 'Flights', 'plane', ! $user || $user->can('flights.search')
                ? route('flights.index')
                : null, 'flights.*');
        }

        foreach (['hotels' => ['Hotels', 'hotel'], 'tours' => ['Tours & Activities', 'palm'], 'visa' => ['Visa', 'passport']] as $key => [$label, $icon]) {
            if (! $this->features->isVisibleTo($key, $user)) {
                continue;
            }

            $service = $services[$key] ?? null;
            $permission = $service['permission'] ?? null;
            $href = null;

            if ($service && $service['available'] && $service['route_name'] && (! $user || ! $permission || $user->can($permission))) {
                $href = route($service['route_name']);
            }

            $items[] = $this->item($key, $label, $icon, $href ?? $this->enquiryUrl($key), $key.'.*');
        }

        if (Route::has('work-visa.index')) {
            $items[] = $this->item('work-visa', 'Work Visa', 'briefcase', route('work-visa.index'), 'work-visa.*', 'New');
        }

        foreach (['packages' => ['Holiday Packages', 'gift'], 'trains' => ['Trains', 'train'], 'buses' => ['Buses', 'bus'], 'cabs' => ['Cabs', 'car'], 'insurance' => ['Insurance', 'shield']] as $key => [$label, $icon]) {
            $href = $key === 'packages' && Route::has('packages.index')
                ? route('packages.index')
                : $this->enquiryUrl($key);

            $items[] = $this->item($key, $label, $icon, $href, $key === 'packages' ? 'packages.*' : null, null, $key);
        }

        return array_values(array_filter($items, fn (array $item): bool => $item['href'] !== ''));
    }

    private function enquiryUrl(string $service): ?string
    {
        return Route::has('enquiries.create')
            ? route('enquiries.create', ['service' => $service])
            : null;
    }

    /**
     * @return array{key: string, label: string, icon: string, href: string, active: bool, badge: string|null}
     */
    private function item(string $key, string $label, string $icon, ?string $href, ?string $activePattern, ?string $badge = null, ?string $enquiryService = null): array
    {
        $active = $activePattern !== null && $this->request->routeIs($activePattern);

        if (! $active && $enquiryService !== null && $this->request->routeIs('enquiries.create')) {
            $active = $this->request->route('service') === $enquiryService;
        }

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'href' => (string) $href,
            'active' => $active,
            'badge' => $badge,
        ];
    }
}
