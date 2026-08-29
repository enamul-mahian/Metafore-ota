<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings
    ) {
    }

    /**
     * List all application settings.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(function (Setting $setting): array {
                return [
                    'group' => $setting->group,
                    'key' => $setting->key,
                    'value' => $this->settings->get(
                        $setting->group,
                        $setting->key
                    ),
                    'type' => $setting->type,
                    'is_public' => $setting->is_public,
                ];
            });

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Create one new application setting.
     */
    public function store(
        UpdateSettingRequest $request,
        string $group,
        string $key
    ): JsonResponse {
        $alreadyExists = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'key' => 'A setting with this group and key already exists.',
            ]);
        }

        $validated = $request->validated();

        try {
            $setting = $this->settings->set(
                group: $group,
                key: $key,
                value: $validated['value'] ?? null,
                type: $validated['type'],
                isPublic: (bool) $validated['is_public'],
            );
        } catch (InvalidArgumentException|JsonException $exception) {
            throw ValidationException::withMessages([
                'value' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Setting created successfully.',
            'data' => [
                'group' => $setting->group,
                'key' => $setting->key,
                'value' => $this->settings->get(
                    $setting->group,
                    $setting->key
                ),
                'type' => $setting->type,
                'is_public' => $setting->is_public,
            ],
        ], 201);
    }

    /**
     * Show one application setting.
     */
    public function show(
        string $group,
        string $key
    ): JsonResponse {
        $setting = Setting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'group' => $setting->group,
                'key' => $setting->key,
                'value' => $this->settings->get(
                    $setting->group,
                    $setting->key
                ),
                'type' => $setting->type,
                'is_public' => $setting->is_public,
            ],
        ]);
    }

    /**
     * Update one application setting.
     */
    public function update(
        UpdateSettingRequest $request,
        string $group,
        string $key
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $setting = $this->settings->set(
                group: $group,
                key: $key,
                value: $validated['value'] ?? null,
                type: $validated['type'],
                isPublic: (bool) $validated['is_public'],
            );
        } catch (InvalidArgumentException|JsonException $exception) {
            throw ValidationException::withMessages([
                'value' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Setting saved successfully.',
            'data' => [
                'group' => $setting->group,
                'key' => $setting->key,
                'value' => $this->settings->get(
                    $setting->group,
                    $setting->key
                ),
                'type' => $setting->type,
                'is_public' => $setting->is_public,
            ],
        ]);
    }

    /**
     * Delete one application setting.
     */
    public function destroy(
        string $group,
        string $key
    ): JsonResponse {
        $deleted = $this->settings->delete(
            $group,
            $key
        );

        abort_unless($deleted, 404);

        return response()->json([
            'message' => 'Setting deleted successfully.',
        ]);
    }
}