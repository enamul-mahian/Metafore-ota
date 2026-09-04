<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAgentRequest;
use App\Models\Agent;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        if (! in_array($status, Agent::STATUSES, true)) {
            $status = '';
        }

        $agents = Agent::query()
            ->with('country:id,name,iso2')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('company_name', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.agents.index', [
            'agents' => $agents,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => Agent::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.agents.create', [
            'countries' => $this->countries(),
            'statuses' => Agent::STATUSES,
        ]);
    }

    public function store(SaveAgentRequest $request): RedirectResponse
    {
        $agent = Agent::query()->create($request->validated());

        return redirect()
            ->route('admin.agents.show', $agent)
            ->with('status', 'Agent created successfully.');
    }

    public function show(Agent $agent): View
    {
        return view('admin.agents.show', [
            'agent' => $agent->load('country:id,name,iso2'),
        ]);
    }

    public function edit(Agent $agent): View
    {
        return view('admin.agents.edit', [
            'agent' => $agent,
            'countries' => $this->countries(),
            'statuses' => Agent::STATUSES,
        ]);
    }

    public function update(SaveAgentRequest $request, Agent $agent): RedirectResponse
    {
        $agent->update($request->validated());

        return redirect()
            ->route('admin.agents.show', $agent)
            ->with('status', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return redirect()
            ->route('admin.agents.index')
            ->with('status', 'Agent deleted successfully.');
    }

    /** @return Collection<int, Country> */
    private function countries(): Collection
    {
        return Country::query()->orderBy('name')->get(['id', 'name', 'iso2']);
    }
}
