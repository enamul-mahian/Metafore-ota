<?php

namespace App\Http\Controllers\WorkVisa;

use App\Enums\WorkVisaApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkVisa\StoreWorkVisaApplicationRequest;
use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDestination;
use App\Services\WorkVisa\WorkVisaDocumentStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WorkVisaApplicationController extends Controller
{
    public function create(Request $request): View
    {
        $destinations = WorkVisaDestination::query()
            ->active()
            ->ordered()
            ->with('country:id,name,iso2')
            ->get();

        $requested = (string) $request->query('destination', '');
        $selected = $destinations->firstWhere('slug', $requested);

        return view('work-visa.apply', [
            'destinations' => $destinations,
            'selected' => $selected,
            'user' => $request->user(),
        ]);
    }

    public function store(StoreWorkVisaApplicationRequest $request, WorkVisaDocumentStore $documents): RedirectResponse
    {
        $data = $request->safe()->except(['passport', 'photo', 'cv', 'certificates', 'confirm']);
        $storedPaths = [];

        try {
            $application = DB::transaction(function () use ($request, $data, $documents, &$storedPaths): WorkVisaApplication {
                $application = WorkVisaApplication::query()->create([
                    ...$data,
                    'reference' => WorkVisaApplication::generateReference(),
                    'user_id' => $request->user()->getKey(),
                    'status' => WorkVisaApplicationStatus::Submitted,
                ]);

                $application->events()->create([
                    'status' => WorkVisaApplicationStatus::Submitted,
                    'note' => 'Application submitted online.',
                    'user_id' => $request->user()->getKey(),
                ]);

                foreach ($this->uploadedFiles($request) as [$type, $file]) {
                    $storedPaths[] = $documents->store($application, $file, $type)->path;
                }

                return $application;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        return redirect()
            ->route('work-visa.applications.show', $application)
            ->with('work_visa_submitted', true)
            ->with('status', 'Your work visa application has been submitted. Reference: '.$application->reference.'.');
    }

    public function index(Request $request): View
    {
        $applications = WorkVisaApplication::query()
            ->whereBelongsTo($request->user())
            ->with('destination.country:id,name,iso2')
            ->latest('id')
            ->paginate(10);

        return view('work-visa.applications.index', [
            'applications' => $applications,
        ]);
    }

    public function show(Request $request, WorkVisaApplication $application): View
    {
        abort_unless($application->user_id === $request->user()->getKey(), 404);

        return view('work-visa.applications.show', [
            'application' => $application->load(['destination.country:id,name,iso2', 'documents', 'events']),
        ]);
    }

    /**
     * @return list<array{0: string, 1: UploadedFile}>
     */
    private function uploadedFiles(StoreWorkVisaApplicationRequest $request): array
    {
        $files = [];

        foreach (['passport' => 'passport', 'photo' => 'photo', 'cv' => 'cv'] as $field => $type) {
            $file = $request->file($field);

            if ($file instanceof UploadedFile) {
                $files[] = [$type, $file];
            }
        }

        foreach ((array) $request->file('certificates', []) as $file) {
            if ($file instanceof UploadedFile) {
                $files[] = ['certificate', $file];
            }
        }

        return $files;
    }
}
