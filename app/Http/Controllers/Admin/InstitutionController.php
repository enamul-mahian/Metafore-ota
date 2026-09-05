<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveInstitutionRequest;
use App\Models\Country;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        if (! in_array($status, Institution::STATUSES, true)) {
            $status = '';
        }

        $institutions = Institution::query()->with('country:id,name,iso2')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.institutions.index', [
            'institutions' => $institutions,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => Institution::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.institutions.create', ['countries' => $this->countries(), 'statuses' => Institution::STATUSES]);
    }

    public function store(SaveInstitutionRequest $request): RedirectResponse
    {
        $institution = Institution::query()->create($request->validated());

        return redirect()->route('admin.institutions.show', $institution)->with('status', 'Institution created successfully.');
    }

    public function show(Institution $institution): View
    {
        return view('admin.institutions.show', ['institution' => $institution->load('country:id,name,iso2')]);
    }

    public function edit(Institution $institution): View
    {
        return view('admin.institutions.edit', [
            'institution' => $institution, 'countries' => $this->countries(), 'statuses' => Institution::STATUSES,
        ]);
    }

    public function update(SaveInstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $institution->update($request->validated());

        return redirect()->route('admin.institutions.show', $institution)->with('status', 'Institution updated successfully.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $institution->delete();

        return redirect()->route('admin.institutions.index')->with('status', 'Institution deleted successfully.');
    }

    /** @return Collection<int, Country> */
    private function countries(): Collection
    {
        return Country::query()->orderBy('name')->get(['id', 'name', 'iso2']);
    }
}
