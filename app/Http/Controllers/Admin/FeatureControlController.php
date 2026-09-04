<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeatureControlRequest;
use App\Services\Feature\FeatureManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class FeatureControlController extends Controller
{
    public function __construct(
        private readonly FeatureManager $features,
    ) {}

    public function index(): View
    {
        return view('admin.features.index', [
            'features' => $this->features->all(),
        ]);
    }

    public function update(
        UpdateFeatureControlRequest $request,
        string $feature,
    ): RedirectResponse {
        abort_unless($this->features->isRegistered($feature), 404);

        $state = $this->features->update(
            $feature,
            $request->validated(),
        );

        return to_route('admin.features.index')
            ->with(
                'status',
                sprintf(
                    '%s feature is now %s.',
                    ucfirst($feature),
                    $state['enabled'] ? 'enabled' : 'disabled',
                ),
            );
    }
}
