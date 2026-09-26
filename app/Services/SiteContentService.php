<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-editable content for the public website: images, SEO text,
 * tracking integrations, contact details and homepage destinations.
 *
 * Everything is stored through SettingService in the "site" group as one
 * JSON document per section, so each page load reads a handful of cached
 * values instead of one per image.
 */
class SiteContentService
{
    public const GROUP = 'site';

    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'site-images';

    /** @var array<string, array<string, mixed>> */
    private array $memo = [];

    private ?bool $settingsTableExists = null;

    public function __construct(private readonly SettingService $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function section(string $key): array
    {
        if (! array_key_exists($key, $this->memo)) {
            // The public site must still render (with defaults) before migrations run.
            $this->settingsTableExists ??= Schema::hasTable('settings');
            $value = $this->settingsTableExists
                ? $this->settings->get(self::GROUP, $key, [])
                : [];
            $this->memo[$key] = is_array($value) ? $value : [];
        }

        return $this->memo[$key];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function saveSection(string $key, array $value): void
    {
        $this->settings->set(self::GROUP, $key, $value, 'json');
        unset($this->memo[$key]);
    }

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, array{label: string, default: string}>
     */
    public function imageDefinitions(): array
    {
        return config('site.images', []);
    }

    public function imageUrl(string $key): string
    {
        $uploaded = $this->section('images')[$key] ?? null;

        if (is_string($uploaded) && $uploaded !== '') {
            return Storage::disk(self::IMAGE_DISK)->url($uploaded);
        }

        $default = $this->imageDefinitions()[$key]['default'] ?? null;

        return is_string($default) ? asset($default) : '';
    }

    public function hasCustomImage(string $key): bool
    {
        $uploaded = $this->section('images')[$key] ?? null;

        return is_string($uploaded) && $uploaded !== '';
    }

    public function setImage(string $key, ?string $path): void
    {
        $images = $this->section('images');
        $previous = $images[$key] ?? null;

        if ($path === null) {
            unset($images[$key]);
        } else {
            $images[$key] = $path;
        }

        $this->saveSection('images', $images);

        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            Storage::disk(self::IMAGE_DISK)->delete($previous);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{title: string, description: string}
     */
    public function seoFor(?string $routeName): array
    {
        $defaults = $routeName !== null ? (config('site.seo_pages', [])[$routeName] ?? []) : [];
        $override = $routeName ? ($this->section('seo')[$routeName] ?? []) : [];

        return [
            'title' => (string) (($override['title'] ?? '') ?: ($defaults['title'] ?? '')),
            'description' => (string) (($override['description'] ?? '') ?: ($defaults['description'] ?? '')),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking & custom code
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, string>
     */
    public function tracking(): array
    {
        $saved = $this->section('tracking');
        $values = [];

        foreach (array_keys(config('site.tracking_fields', [])) as $field) {
            $value = $saved[$field] ?? '';
            $values[$field] = is_string($value) ? $value : '';
        }

        return $values;
    }

    /**
     * @return array{head: string, body: string}
     */
    public function customCode(): array
    {
        $saved = $this->section('custom_code');

        return [
            'head' => is_string($saved['head'] ?? null) ? $saved['head'] : '',
            'body' => is_string($saved['body'] ?? null) ? $saved['body'] : '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Contact details
    |--------------------------------------------------------------------------
    */

    /**
     * Only fields an admin actually filled in are returned.
     *
     * @return array<string, string>
     */
    public function contact(): array
    {
        $saved = $this->section('contact');

        return collect(config('site.contact_fields', []))
            ->keys()
            ->mapWithKeys(fn (string $field): array => [$field => trim((string) ($saved[$field] ?? ''))])
            ->filter(fn (string $value): bool => $value !== '')
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Homepage destinations
    |--------------------------------------------------------------------------
    */

    /**
     * @return list<array{name: string, country: string, image: string, active: bool, sort: int}>
     */
    public function destinations(bool $onlyActive = false): array
    {
        $saved = $this->section('destinations');
        $items = array_key_exists('items', $saved) && is_array($saved['items'])
            ? $saved['items']
            : $this->defaultDestinations();

        $items = collect($items)
            ->filter(fn ($item): bool => is_array($item) && trim((string) ($item['name'] ?? '')) !== '')
            ->map(fn (array $item, int $index): array => [
                'name' => (string) $item['name'],
                'country' => (string) ($item['country'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
                'active' => (bool) ($item['active'] ?? true),
                'sort' => (int) ($item['sort'] ?? $index),
            ])
            ->sortBy('sort')
            ->values();

        if ($onlyActive) {
            $items = $items->where('active', true)->values();
        }

        return $items->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function saveDestinations(array $items): void
    {
        $this->saveSection('destinations', ['items' => array_values($items)]);
    }

    /**
     * Image reference of a destination: an image key from config('site.images')
     * or a stored path on the public disk.
     */
    public function destinationImageUrl(string $image): string
    {
        if ($image === '') {
            return '';
        }

        if (array_key_exists($image, $this->imageDefinitions())) {
            return $this->imageUrl($image);
        }

        return Storage::disk(self::IMAGE_DISK)->url($image);
    }

    /**
     * @return list<array{name: string, country: string, image: string, active: bool, sort: int}>
     */
    private function defaultDestinations(): array
    {
        return [
            ['name' => 'Dubai', 'country' => 'UAE', 'image' => 'dest.dubai', 'active' => true, 'sort' => 0],
            ['name' => 'Bali', 'country' => 'Indonesia', 'image' => 'dest.bali', 'active' => true, 'sort' => 1],
            ['name' => 'Bangkok', 'country' => 'Thailand', 'image' => 'dest.bangkok', 'active' => true, 'sort' => 2],
            ['name' => 'Singapore', 'country' => 'Singapore', 'image' => 'dest.singapore', 'active' => true, 'sort' => 3],
            ['name' => 'Maldives', 'country' => 'Maldives', 'image' => 'dest.maldives', 'active' => true, 'sort' => 4],
            ['name' => 'London', 'country' => 'UK', 'image' => 'dest.london', 'active' => true, 'sort' => 5],
            ['name' => 'Istanbul', 'country' => 'Turkey', 'image' => 'dest.istanbul', 'active' => true, 'sort' => 6],
            ['name' => 'Paris', 'country' => 'France', 'image' => 'dest.paris', 'active' => true, 'sort' => 7],
        ];
    }
}
