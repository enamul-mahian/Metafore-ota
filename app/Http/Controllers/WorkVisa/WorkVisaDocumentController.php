<?php

namespace App\Http\Controllers\WorkVisa;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkVisa\StoreWorkVisaDocumentRequest;
use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDocument;
use App\Services\WorkVisa\WorkVisaDocumentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkVisaDocumentController extends Controller
{
    public function store(
        StoreWorkVisaDocumentRequest $request,
        WorkVisaApplication $application,
        WorkVisaDocumentStore $documents,
    ): RedirectResponse {
        abort_unless($application->user_id === $request->user()->getKey(), 404);

        if (! $application->status->acceptsDocuments()) {
            return redirect()
                ->route('work-visa.applications.show', $application)
                ->withErrors(['document' => 'Documents can no longer be added to this application.']);
        }

        $documents->store($application, $request->file('document'), $request->string('type')->toString());

        return redirect()
            ->route('work-visa.applications.show', $application)
            ->with('status', 'Document uploaded.');
    }

    /**
     * Stream a document from the private disk to its owner or to staff with work-visa.view.
     */
    public function download(Request $request, WorkVisaDocument $document): StreamedResponse
    {
        $user = $request->user();
        $application = $document->application;

        $isOwner = $application !== null && $application->user_id === $user->getKey();
        $isStaff = $user->hasAnyRole(['admin', 'super-admin']) && $user->can('work-visa.view');

        abort_unless($isOwner || $isStaff, 404);

        $disk = Storage::disk(WorkVisaDocument::DISK);

        abort_unless($disk->exists($document->path), 404);

        return $disk->download($document->path, $document->original_name, [
            'Content-Type' => $document->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
