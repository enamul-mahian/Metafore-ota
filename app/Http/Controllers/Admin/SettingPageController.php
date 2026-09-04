<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;

class SettingPageController extends Controller
{
    public function __construct(
        private readonly SettingService $settings
    ) {}

    public function __invoke(): View
    {
        $items = Setting::query()
            ->where('group', '!=', 'features')
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
                    'updated_at' => $setting->updated_at,
                ];
            });

        $groups = $items->groupBy('group');

        $requestedGroup = (string) request()->query('group', '');

        $activeGroup = $requestedGroup !== '' && $groups->has($requestedGroup)
            ? $requestedGroup
            : ($groups->keys()->first() ?? 'general');

        $activeSettings = $groups->get(
            $activeGroup,
            collect()
        );

        return view('admin.settings.index', [
            'settings' => $items,
            'groups' => $groups,
            'activeGroup' => $activeGroup,
            'activeSettings' => $activeSettings,

            'totalSettings' => $items->count(),
            'publicSettings' => $items
                ->where('is_public', true)
                ->count(),
            'privateSettings' => $items
                ->where('is_public', false)
                ->count(),
            'settingGroups' => $groups->count(),
        ]);
    }
}
