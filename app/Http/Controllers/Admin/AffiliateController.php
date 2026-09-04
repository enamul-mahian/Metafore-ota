<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAffiliateRequest;
use App\Models\Affiliate;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        if (! in_array($status, Affiliate::STATUSES, true)) {
            $status = '';
        }

        $affiliates = Affiliate::query()
            ->with('country:id,name,iso2')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('organization_name', 'like', '%'.$search.'%')
                        ->orWhere('referral_code', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.affiliates.index', [
            'affiliates' => $affiliates,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => Affiliate::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.affiliates.create', ['countries' => $this->countries(), 'statuses' => Affiliate::STATUSES]);
    }

    public function store(SaveAffiliateRequest $request): RedirectResponse
    {
        $affiliate = Affiliate::query()->create($request->validated());

        return redirect()->route('admin.affiliates.show', $affiliate)->with('status', 'Affiliate created successfully.');
    }

    public function show(Affiliate $affiliate): View
    {
        return view('admin.affiliates.show', ['affiliate' => $affiliate->load('country:id,name,iso2')]);
    }

    public function edit(Affiliate $affiliate): View
    {
        return view('admin.affiliates.edit', [
            'affiliate' => $affiliate, 'countries' => $this->countries(), 'statuses' => Affiliate::STATUSES,
        ]);
    }

    public function update(SaveAffiliateRequest $request, Affiliate $affiliate): RedirectResponse
    {
        $affiliate->update($request->validated());

        return redirect()->route('admin.affiliates.show', $affiliate)->with('status', 'Affiliate updated successfully.');
    }

    public function destroy(Affiliate $affiliate): RedirectResponse
    {
        $affiliate->delete();

        return redirect()->route('admin.affiliates.index')->with('status', 'Affiliate deleted successfully.');
    }

    /** @return Collection<int, Country> */
    private function countries(): Collection
    {
        return Country::query()->orderBy('name')->get(['id', 'name', 'iso2']);
    }
}
