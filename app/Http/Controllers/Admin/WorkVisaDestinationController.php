<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveWorkVisaDestinationRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Models\WorkVisaDestination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkVisaDestinationController extends Controller
{
    public function index(): View
    {
        return view('admin.work-visa.destinations.index', [
            'destinations' => WorkVisaDestination::query()
                ->ordered()
                ->with('country:id,name,iso2')
                ->withCount('applications')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.work-visa.destinations.create', [
            'destination' => new WorkVisaDestination(['currency' => 'BDT', 'is_active' => true, 'sort_order' => 0]),
            ...$this->formOptions(),
        ]);
    }

    public function store(SaveWorkVisaDestinationRequest $request): RedirectResponse
    {
        WorkVisaDestination::query()->create($request->validated());

        return redirect()
            ->route('admin.work-visa.destinations.index')
            ->with('status', 'Work visa destination created successfully.');
    }

    public function edit(WorkVisaDestination $destination): View
    {
        return view('admin.work-visa.destinations.edit', [
            'destination' => $destination,
            ...$this->formOptions(),
        ]);
    }

    public function update(SaveWorkVisaDestinationRequest $request, WorkVisaDestination $destination): RedirectResponse
    {
        $destination->update($request->validated());

        return redirect()
            ->route('admin.work-visa.destinations.index')
            ->with('status', 'Work visa destination updated successfully.');
    }

    public function destroy(WorkVisaDestination $destination): RedirectResponse
    {
        if ($destination->applications()->exists()) {
            return redirect()
                ->route('admin.work-visa.destinations.edit', $destination)
                ->withErrors(['destination' => 'This destination has applications. Deactivate it instead of deleting it.']);
        }

        $destination->delete();

        return redirect()
            ->route('admin.work-visa.destinations.index')
            ->with('status', 'Work visa destination deleted successfully.');
    }

    /**
     * @return array{countries: Collection<int, Country>, currencies: list<string>}
     */
    private function formOptions(): array
    {
        $currencies = Currency::query()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->pluck('code')->all();

        return [
            'countries' => Country::query()->orderBy('name')->get(['id', 'name', 'iso2']),
            'currencies' => array_values(array_unique(['BDT', ...$currencies])),
        ];
    }
}
