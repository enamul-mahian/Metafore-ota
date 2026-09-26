@extends('layouts.site')

@section('title', 'Work Visa Application '.$application->reference)
@section('seo_title', 'Work Visa Application '.$application->reference.' | Eagle Global Hub')
@section('robots', 'noindex,nofollow')
@section('body_class', 'wv-body')

@php
    $destination = $application->destination;
    $statusOrder = [
        \App\Enums\WorkVisaApplicationStatus::Submitted,
        \App\Enums\WorkVisaApplicationStatus::DocumentsReview,
        \App\Enums\WorkVisaApplicationStatus::InProcess,
    ];
    $position = array_search($application->status, $statusOrder, true);
    $isFinal = $position === false;
@endphp

@section('content')
<section class="page">
    <div class="container">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span><a href="{{ route('work-visa.applications.index') }}">Work Visa Applications</a></span><span>{{ $application->reference }}</span></nav>

        @if (session('work_visa_submitted'))
            <div class="card done-box wv-done" data-track-on-load="lead" data-track-params='@json(['content_type' => 'work_visa', 'item_name' => ($destination?->name() ?? 'Work').' Work Visa'])' role="status">
                <span class="svc__ic c-green"><svg class="ic"><use href="#i-check"/></svg></span>
                <h2>Application Submitted</h2>
                <p class="muted">Your reference is <b class="red">{{ $application->reference }}</b>. Our team will review your documents and update the status here.</p>
            </div>
        @elseif (session('status'))
            <div class="wv-alert wv-alert--success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="wv-alert wv-alert--error" role="alert">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row row--between wv-head">
            <div>
                <h1 class="h1">{{ $destination?->name() ?? 'Work' }} – Work Visa</h1>
                <p class="muted small">Reference {{ $application->reference }} · Submitted {{ $application->created_at?->format('j M Y') }}</p>
            </div>
            <span class="badge badge--{{ $application->status->badge() }} wv-badge-lg">{{ $application->status->label() }}</span>
        </div>

        <ol class="steps" aria-label="Application progress">
            @foreach (['Submitted', 'Documents Review', 'In Process', 'Decision'] as $index => $label)
                @php
                    $done = $isFinal || ($position !== false && $index < $position);
                    $current = ! $isFinal && $position === $index;
                @endphp
                <li class="step {{ $done ? 'is-done' : '' }} {{ $current ? 'is-current' : '' }}" @if ($current) aria-current="step" @endif><i>{{ $index + 1 }}</i><span>{{ $index === 3 && $isFinal ? $application->status->label() : $label }}</span></li>
            @endforeach
        </ol>

        <div class="layout-side">
            <div class="stack">
                <div class="card">
                    <h2 class="card__title">Applicant</h2>
                    <dl class="wv-dl">
                        <div><dt>Name</dt><dd>{{ $application->fullName() }}</dd></div>
                        <div><dt>Date of birth</dt><dd>{{ $application->date_of_birth?->format('j M Y') }}</dd></div>
                        <div><dt>Gender</dt><dd>{{ ucfirst($application->gender) }}</dd></div>
                        <div><dt>Nationality</dt><dd>{{ $application->nationality }}</dd></div>
                        <div><dt>Passport number</dt><dd>{{ $application->passport_number }}</dd></div>
                        <div><dt>Passport expiry</dt><dd>{{ $application->passport_expiry?->format('j M Y') }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $application->email }}</dd></div>
                        <div><dt>Phone</dt><dd>{{ $application->phone }}</dd></div>
                        <div class="wv-dl__full"><dt>Address</dt><dd>{{ $application->current_address }}</dd></div>
                        <div><dt>Job category</dt><dd>{{ $application->job_category }}</dd></div>
                        <div><dt>Experience</dt><dd>{{ $application->experience_years !== null ? $application->experience_years.' '.str('year')->plural($application->experience_years) : 'Not specified' }}</dd></div>
                        @if ($application->notes)
                            <div class="wv-dl__full"><dt>Your notes</dt><dd class="wv-pre">{{ $application->notes }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="card" id="documents">
                    <h2 class="card__title">Documents <span class="muted small">{{ $application->documents->count() }} {{ str('file')->plural($application->documents->count()) }}</span></h2>
                    @if ($application->documents->isEmpty())
                        <p class="muted small">No documents uploaded.</p>
                    @else
                        <ul class="wv-docs-list">
                            @foreach ($application->documents as $document)
                                <li>
                                    <svg class="ic"><use href="#i-file"/></svg>
                                    <div>
                                        <b>{{ $document->typeLabel() }}</b>
                                        <small>{{ $document->original_name }} · {{ $document->humanSize() }}</small>
                                    </div>
                                    <a href="{{ route('work-visa.documents.download', $document) }}" class="btn btn--outline btn--sm"><svg class="ic"><use href="#i-download"/></svg>Download <span class="wv-sr">{{ $document->original_name }}</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($application->status->acceptsDocuments())
                        <form method="POST" action="{{ route('work-visa.applications.documents.store', $application) }}" enctype="multipart/form-data" class="wv-add-doc" aria-label="Upload another document">
                            @csrf
                            <h3 class="form-sec"><svg class="ic"><use href="#i-upload"/></svg>Add a document</h3>
                            <div class="form-grid form-grid--2">
                                <div class="field">
                                    <label for="wvDocType">Document type</label>
                                    <select class="input" id="wvDocType" name="type" required>
                                        @foreach (\App\Models\WorkVisaDocument::TYPES as $value => $label)
                                            <option value="{{ $value }}" @selected(old('type', 'other') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label class="upload" for="wvDocFile">
                                    <input type="file" id="wvDocFile" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                    <svg class="ic"><use href="#i-upload"/></svg>
                                    <b>Choose file</b>
                                    <span class="fname" data-file-name data-empty="PDF, JPG or PNG up to 5 MB">PDF, JPG or PNG up to 5 MB</span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn--blue wv-add-doc__btn"><svg class="ic"><use href="#i-upload"/></svg>Upload Document</button>
                        </form>
                    @endif
                </div>

                <div class="card">
                    <h2 class="card__title">Status History</h2>
                    <ol class="wv-timeline">
                        @foreach ($application->events->reverse() as $event)
                            <li class="wv-timeline__item wv-timeline__item--{{ $event->status->badge() }}">
                                <div class="wv-timeline__head">
                                    <span class="badge badge--{{ $event->status->badge() }}">{{ $event->status->label() }}</span>
                                    <time datetime="{{ $event->created_at?->toIso8601String() }}">{{ $event->created_at?->format('j M Y, g:i A') }}</time>
                                </div>
                                @if ($event->note)
                                    <p class="wv-pre">{{ $event->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <aside class="summary" aria-label="Application summary">
                <h2 class="card__title">Application Summary</h2>
                <div class="summary__item">
                    <span class="sum-flag" aria-hidden="true">{{ $destination?->flag() }}</span>
                    <div><b class="h3">{{ $destination?->name() ?? 'Work' }} – Work Visa</b><br><span class="muted small">{{ $destination?->visa_type ?: 'Work visa' }}</span></div>
                </div>
                <div class="summary__row"><span>Reference</span><b>{{ $application->reference }}</b></div>
                <div class="summary__row"><span>Status</span><span class="badge badge--{{ $application->status->badge() }}">{{ $application->status->label() }}</span></div>
                <div class="summary__row"><span>Estimated processing</span><b>{{ $destination?->processing_time ?: '—' }}</b></div>
                <div class="summary__row"><span>Job category</span><span>{{ $application->job_category }}</span></div>
                <div class="summary__total"><span>Service fee</span><span>{{ $destination?->formattedFee() ?? 'On request' }}</span></div>
                <a href="{{ route('work-visa.applications.index') }}" class="btn btn--outline btn--block">All Applications</a>
                <p class="secure"><svg class="ic"><use href="#i-lock"/></svg>Your documents are stored privately</p>
            </aside>
        </div>
    </div>
</section>
@endsection
