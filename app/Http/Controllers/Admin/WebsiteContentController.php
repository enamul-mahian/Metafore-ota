<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWebsiteContactRequest;
use App\Http\Requests\Admin\UpdateWebsiteCustomCodeRequest;
use App\Http\Requests\Admin\UpdateWebsiteDestinationsRequest;
use App\Http\Requests\Admin\UpdateWebsiteImageRequest;
use App\Http\Requests\Admin\UpdateWebsiteSeoRequest;
use App\Http\Requests\Admin\UpdateWebsiteTrackingRequest;
use App\Services\SiteContentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Admin > Website: everything customers see that admins can change without a deploy.
 */
class WebsiteContentController extends Controller
{
    public function __construct(
        private readonly SiteContentService $site
    ) {}

    public function images(): View
    {
        $images = collect($this->site->imageDefinitions())
            ->map(fn (array $definition, string $key): array => [
                'key' => $key,
                'label' => $definition['label'],
                'url' => $this->site->imageUrl($key),
                'custom' => $this->site->hasCustomImage($key),
                'group' => str_contains($key, '.') ? strtok($key, '.') : 'brand',
            ])
            ->groupBy('group');

        return $this->page('images', ['imageGroups' => $images]);
    }

    public function updateImage(UpdateWebsiteImageRequest $request, string $key): RedirectResponse
    {
        abort_unless(array_key_exists($key, $this->site->imageDefinitions()), 404);

        $path = $this->storeImage($request->file('image'));
        $this->site->setImage($key, $path);

        return back()->with('status', 'Image updated: '.$this->site->imageDefinitions()[$key]['label'].'.');
    }

    public function resetImage(string $key): RedirectResponse
    {
        abort_unless(array_key_exists($key, $this->site->imageDefinitions()), 404);

        $this->site->setImage($key, null);

        return back()->with('status', 'Image restored to the default: '.$this->site->imageDefinitions()[$key]['label'].'.');
    }

    public function destinations(): View
    {
        return $this->page('destinations', [
            'destinations' => collect($this->site->destinations())
                ->map(fn (array $item): array => $item + ['url' => $this->site->destinationImageUrl($item['image'])])
                ->all(),
            'imageOptions' => collect($this->site->imageDefinitions())
                ->filter(fn (array $definition, string $key): bool => str_starts_with($key, 'dest.'))
                ->map(fn (array $definition): string => $definition['label'])
                ->all(),
        ]);
    }

    public function updateDestinations(UpdateWebsiteDestinationsRequest $request): RedirectResponse
    {
        $current = collect($this->site->destinations())->pluck('image')->all();
        $items = [];

        foreach ($request->validated('items', []) as $index => $item) {
            if (! empty($item['remove'])) {
                continue;
            }

            $image = (string) ($item['image'] ?? '');
            $upload = $request->file('items.'.$index.'.upload');

            if ($upload instanceof UploadedFile) {
                $image = $this->storeImage($upload);
            } elseif (! $this->isAllowedDestinationImage($image, $current)) {
                $image = '';
            }

            $items[] = [
                'name' => trim($item['name']),
                'country' => trim((string) ($item['country'] ?? '')),
                'image' => $image,
                'active' => (bool) ($item['active'] ?? false),
                'sort' => (int) ($item['sort'] ?? count($items)),
            ];
        }

        $this->site->saveDestinations($items);
        $this->deleteUnusedDestinationUploads($current, array_column($items, 'image'));

        return back()->with('status', 'Homepage destinations saved.');
    }

    public function seo(): View
    {
        $saved = $this->site->section('seo');

        $pages = collect(config('site.seo_pages', []))
            ->map(fn (array $defaults, string $routeName): array => [
                'route' => $routeName,
                'label' => $defaults['label'],
                'url' => Route::has($routeName) ? route($routeName) : null,
                'default_title' => $defaults['title'],
                'default_description' => $defaults['description'],
                'title' => (string) ($saved[$routeName]['title'] ?? ''),
                'description' => (string) ($saved[$routeName]['description'] ?? ''),
            ])
            ->values();

        return $this->page('seo', ['seoPages' => $pages]);
    }

    public function updateSeo(UpdateWebsiteSeoRequest $request): RedirectResponse
    {
        $input = (array) $request->input('pages', []);
        $seo = [];

        foreach (array_keys(config('site.seo_pages', [])) as $routeName) {
            $title = trim((string) ($input[$routeName]['title'] ?? ''));
            $description = trim((string) ($input[$routeName]['description'] ?? ''));

            if ($title !== '' || $description !== '') {
                $seo[$routeName] = ['title' => $title, 'description' => $description];
            }
        }

        $this->site->saveSection('seo', $seo);

        return back()->with('status', 'SEO settings saved.');
    }

    public function tracking(): View
    {
        return $this->page('tracking', [
            'trackingFields' => config('site.tracking_fields', []),
            'tracking' => $this->site->tracking(),
            'customCode' => $this->site->customCode(),
        ]);
    }

    public function updateTracking(UpdateWebsiteTrackingRequest $request): RedirectResponse
    {
        $this->site->saveSection('tracking', array_map(
            fn (?string $value): string => trim((string) $value),
            $request->validated()
        ));

        return back()->with('status', 'Tracking & ads settings saved.');
    }

    public function updateCustomCode(UpdateWebsiteCustomCodeRequest $request): RedirectResponse
    {
        $this->site->saveSection('custom_code', [
            'head' => (string) $request->validated('head', ''),
            'body' => (string) $request->validated('body', ''),
        ]);

        return back()->with('status', 'Custom code saved. It now runs on every public page.');
    }

    public function contact(): View
    {
        return $this->page('contact', [
            'contactFields' => config('site.contact_fields', []),
            'contact' => $this->site->section('contact'),
        ]);
    }

    public function updateContact(UpdateWebsiteContactRequest $request): RedirectResponse
    {
        $this->site->saveSection('contact', array_map(
            fn (?string $value): string => trim((string) $value),
            $request->validated()
        ));

        return back()->with('status', 'Contact details saved.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function page(string $tab, array $data): View
    {
        return view('admin.website.'.$tab, $data + [
            'activeTab' => $tab,
            'canManage' => (bool) auth()->user()?->can('content.manage'),
        ]);
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store(SiteContentService::IMAGE_DIRECTORY, SiteContentService::IMAGE_DISK);
    }

    /**
     * @param  list<string>  $current
     */
    private function isAllowedDestinationImage(string $image, array $current): bool
    {
        return $image === ''
            || array_key_exists($image, $this->site->imageDefinitions())
            || in_array($image, $current, true);
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    private function deleteUnusedDestinationUploads(array $before, array $after): void
    {
        foreach (array_diff($before, $after) as $image) {
            if (str_starts_with($image, SiteContentService::IMAGE_DIRECTORY.'/')) {
                Storage::disk(SiteContentService::IMAGE_DISK)->delete($image);
            }
        }
    }
}
