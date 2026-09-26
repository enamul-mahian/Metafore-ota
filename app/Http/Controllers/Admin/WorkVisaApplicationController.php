<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkVisaApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWorkVisaApplicationStatusRequest;
use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDestination;
use App\Notifications\WorkVisaApplicationStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkVisaApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = WorkVisaApplicationStatus::tryFrom((string) $request->query('status', ''));
        $destinationId = (int) $request->query('destination', 0);

        $applications = WorkVisaApplication::query()
            ->with(['destination.country:id,name,iso2', 'user:id,name,email'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('passport_number', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($destinationId > 0, fn ($query) => $query->where('work_visa_destination_id', $destinationId))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.work-visa.applications.index', [
            'applications' => $applications,
            'filters' => [
                'search' => $search,
                'status' => $status?->value ?? '',
                'destination' => $destinationId > 0 ? (string) $destinationId : '',
            ],
            'statuses' => WorkVisaApplicationStatus::cases(),
            'destinations' => WorkVisaDestination::query()->ordered()->with('country:id,name,iso2')->get(),
        ]);
    }

    public function show(WorkVisaApplication $application): View
    {
        return view('admin.work-visa.applications.show', [
            'application' => $application->load([
                'destination.country:id,name,iso2',
                'user:id,name,email',
                'documents',
                'events.user:id,name',
            ]),
            'statuses' => WorkVisaApplicationStatus::cases(),
        ]);
    }

    public function updateStatus(UpdateWorkVisaApplicationStatusRequest $request, WorkVisaApplication $application): RedirectResponse
    {
        $status = WorkVisaApplicationStatus::from($request->string('status')->toString());
        $note = $request->validated('note');

        DB::transaction(function () use ($application, $status, $note, $request): void {
            $application->update([
                'status' => $status,
                'admin_note' => $note ?? $application->admin_note,
            ]);

            $application->events()->create([
                'status' => $status,
                'note' => $note,
                'user_id' => $request->user()->getKey(),
            ]);
        });

        $message = 'Status updated to '.$status->label().'.';

        if ($request->boolean('notify_applicant') && $application->user !== null) {
            $application->user->notify(new WorkVisaApplicationStatusUpdated($application, $note));
            $message .= ' The applicant has been emailed.';
        }

        return redirect()
            ->route('admin.work-visa.applications.show', $application)
            ->with('status', $message);
    }
}
