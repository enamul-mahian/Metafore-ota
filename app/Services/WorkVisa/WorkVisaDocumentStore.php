<?php

namespace App\Services\WorkVisa;

use App\Models\WorkVisaApplication;
use App\Models\WorkVisaDocument;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class WorkVisaDocumentStore
{
    /**
     * Store an uploaded file on the private disk under work-visa/{application}/.
     */
    public function store(WorkVisaApplication $application, UploadedFile $file, string $type): WorkVisaDocument
    {
        $path = $file->store('work-visa/'.$application->getKey(), WorkVisaDocument::DISK);

        if ($path === false) {
            throw new RuntimeException('The document could not be stored.');
        }

        return $application->documents()->create([
            'type' => $type,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'path' => $path,
            'mime' => (string) ($file->getMimeType() ?? $file->getClientMimeType()),
            'size' => (int) $file->getSize(),
        ]);
    }
}
