@extends('layouts.site')

@section('title', 'Work Visa Application')
@section('seo_title', 'Apply for a Work Visa Online | Eagle Global Hub')
@section('seo_description', 'Apply for a work visa online with Eagle Global Hub. Choose your destination and job category, add your passport details and upload documents securely.')
@section('robots', 'noindex,nofollow')
@section('body_class', 'wv-body wv-apply-body')

@php
    $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
    $defaultFirst = $nameParts[0] ?? '';
    $defaultLast = $nameParts[1] ?? '';
    $selectedId = (string) old('work_visa_destination_id', $selected?->id ?? ($destinations->count() === 1 ? $destinations->first()->id : ''));
    $current = $destinations->firstWhere('id', (int) $selectedId);
    $otherCategory = \App\Http\Requests\WorkVisa\StoreWorkVisaApplicationRequest::OTHER_JOB_CATEGORY;

    $destinationData = $destinations->mapWithKeys(fn ($destination) => [
        $destination->id => [
            'name' => $destination->name(),
            'flag' => $destination->flag(),
            'visaType' => $destination->visa_type ?: 'Work visa',
            'time' => $destination->processing_time ?: 'Confirmed after review',
            'fee' => $destination->formattedFee(),
            'categories' => $destination->jobCategoryList(),
        ],
    ]);
    $categories = $current?->jobCategoryList() ?? [];
    $oldCategory = (string) old('job_category', '');
    $fieldError = fn (string $key) => $errors->has($key) ? 'aria-invalid=true aria-describedby='.str_replace(['.', '_'], '-', $key).'-error' : '';
@endphp

@section('content')
<section class="page wv-apply">
    <div class="container">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span><a href="{{ route('work-visa.index') }}">Work Visa</a></span><span>Application</span></nav>
        <h1 class="h1 wv-title">Work Visa Application</h1>

        <ol class="steps" id="wvSteps" aria-label="Application progress">
            <li class="step" data-step="1"><i>1</i><span>Select Country</span></li>
            <li class="step" data-step="2"><i>2</i><span>Visa Category</span></li>
            <li class="step" data-step="3"><i>3</i><span>Personal Info</span></li>
            <li class="step" data-step="4"><i>4</i><span>Documents</span></li>
            <li class="step" data-step="5"><i>5</i><span>Review</span></li>
            <li class="step" data-step="6"><i>6</i><span>Submission</span></li>
        </ol>

        @if ($destinations->isEmpty())
            <div class="card empty">
                <svg class="ic"><use href="#i-briefcase"/></svg>
                <p class="h3">Online applications are not open yet</p>
                <p>No work visa destinations are open for online applications right now.</p>
                @if (Route::has('enquiries.create'))
                    <a href="{{ route('enquiries.create', 'visa') }}" class="btn btn--blue">Send a Work Visa Enquiry <svg class="ic"><use href="#i-arrow-r"/></svg></a>
                @else
                    <a href="{{ route('work-visa.index') }}" class="btn btn--outline">Back to Work Visa</a>
                @endif
            </div>
        @else
            @if ($errors->any())
                <div class="wv-alert wv-alert--error" role="alert" tabindex="-1" id="wvErrors">
                    <b>Please check the highlighted fields.</b>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="layout-side wv-layout">
                <form class="card wv-form" id="wvForm" method="POST" action="{{ route('work-visa.store') }}" enctype="multipart/form-data" autocomplete="on" aria-label="Work visa application">
                    @csrf

                    <h2 class="card__title">Destination &amp; Job</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label for="wvCountry">Destination country <em>*</em></label>
                            <select class="input" id="wvCountry" name="work_visa_destination_id" required {!! $fieldError('work_visa_destination_id') !!}>
                                <option value="">Select country</option>
                                @foreach ($destinations as $destination)
                                    <option value="{{ $destination->id }}" @selected($selectedId === (string) $destination->id)>{{ $destination->name() }}</option>
                                @endforeach
                            </select>
                            @error('work_visa_destination_id')<p class="wv-error" id="work-visa-destination-id-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvJob">Job category <em>*</em></label>
                            <select class="input" id="wvJob" name="job_category" required {!! $fieldError('job_category') !!}>
                                <option value="">Select job category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($oldCategory === $category)>{{ $category }}</option>
                                @endforeach
                                <option value="{{ $otherCategory }}" @selected($oldCategory === $otherCategory)>Other (describe in notes)</option>
                            </select>
                            @error('job_category')<p class="wv-error" id="job-category-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvExp">Work experience (years)</label>
                            <input class="input" id="wvExp" name="experience_years" type="number" inputmode="numeric" min="0" max="50" step="1" value="{{ old('experience_years') }}" placeholder="e.g. 3" {!! $fieldError('experience_years') !!}>
                            @error('experience_years')<p class="wv-error" id="experience-years-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h2 class="form-sec"><svg class="ic"><use href="#i-user"/></svg>Personal Information</h2>
                    <div class="form-grid">
                        <div class="field">
                            <label for="wvTitle">Title</label>
                            <select class="input" id="wvTitle" name="title" autocomplete="honorific-prefix">
                                <option value="">—</option>
                                @foreach (\App\Models\WorkVisaApplication::TITLES as $title)
                                    <option value="{{ $title }}" @selected(old('title') === $title)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="wvFirst">First name <em>*</em></label>
                            <input class="input" id="wvFirst" name="first_name" required maxlength="100" autocomplete="given-name" placeholder="As in passport" value="{{ old('first_name', $defaultFirst) }}" {!! $fieldError('first_name') !!}>
                            @error('first_name')<p class="wv-error" id="first-name-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvLast">Last name <em>*</em></label>
                            <input class="input" id="wvLast" name="last_name" required maxlength="100" autocomplete="family-name" placeholder="As in passport" value="{{ old('last_name', $defaultLast) }}" {!! $fieldError('last_name') !!}>
                            @error('last_name')<p class="wv-error" id="last-name-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvDob">Date of birth <em>*</em></label>
                            <input class="input" id="wvDob" name="date_of_birth" type="date" required autocomplete="bday" max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('date_of_birth') }}" {!! $fieldError('date_of_birth') !!}>
                            @error('date_of_birth')<p class="wv-error" id="date-of-birth-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field span-2">
                            <span id="wvGenderLbl">Gender <em class="red">*</em></span>
                            <div class="radio-row" role="radiogroup" aria-labelledby="wvGenderLbl">
                                @foreach (\App\Models\WorkVisaApplication::GENDERS as $gender)
                                    <label class="radio"><input type="radio" name="gender" value="{{ $gender }}" required @checked(old('gender', 'male') === $gender)><span></span>{{ ucfirst($gender) }}</label>
                                @endforeach
                            </div>
                            @error('gender')<p class="wv-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvNat">Nationality <em>*</em></label>
                            <input class="input" id="wvNat" name="nationality" required maxlength="100" autocomplete="off" value="{{ old('nationality', 'Bangladeshi') }}" {!! $fieldError('nationality') !!}>
                            @error('nationality')<p class="wv-error" id="nationality-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h2 class="form-sec"><svg class="ic"><use href="#i-passport"/></svg>Passport</h2>
                    <div class="form-grid form-grid--2">
                        <div class="field">
                            <label for="wvPass">Passport number <em>*</em></label>
                            <input class="input" id="wvPass" name="passport_number" required maxlength="20" autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="e.g. A01234567" value="{{ old('passport_number') }}" {!! $fieldError('passport_number') !!}>
                            @error('passport_number')<p class="wv-error" id="passport-number-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvPassExp">Passport expiry date <em>*</em></label>
                            <input class="input" id="wvPassExp" name="passport_expiry" type="date" required min="{{ now()->addDay()->toDateString() }}" value="{{ old('passport_expiry') }}" {!! $fieldError('passport_expiry') !!}>
                            @error('passport_expiry')<p class="wv-error" id="passport-expiry-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h2 class="form-sec"><svg class="ic"><use href="#i-phone"/></svg>Contact Details</h2>
                    <div class="form-grid form-grid--2">
                        <div class="field">
                            <label for="wvEmail">Email <em>*</em></label>
                            <input class="input" id="wvEmail" name="email" type="email" inputmode="email" required maxlength="255" autocomplete="email" placeholder="you@example.com" value="{{ old('email', $user->email) }}" {!! $fieldError('email') !!}>
                            @error('email')<p class="wv-error" id="email-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="wvPhone">Mobile number <em>*</em></label>
                            <input class="input" id="wvPhone" name="phone" type="tel" inputmode="tel" required maxlength="32" autocomplete="tel" placeholder="+880 1XXX XXXXXX" value="{{ old('phone') }}" {!! $fieldError('phone') !!}>
                            @error('phone')<p class="wv-error" id="phone-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field span-all">
                            <label for="wvAddr">Current address <em>*</em></label>
                            <input class="input" id="wvAddr" name="current_address" required maxlength="500" autocomplete="street-address" placeholder="House, road, area, district" value="{{ old('current_address') }}" {!! $fieldError('current_address') !!}>
                            @error('current_address')<p class="wv-error" id="current-address-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <h2 class="form-sec"><svg class="ic"><use href="#i-file"/></svg>Documents <span class="muted small wv-hint">PDF, JPG or PNG, up to 5 MB each</span></h2>
                    <div class="upload-grid">
                        @foreach ([
                            ['passport', 'Passport (bio page) *', 'i-passport', false, true],
                            ['photo', 'Passport-size photo', 'i-image', false, false],
                            ['cv', 'CV / Resume', 'i-file', false, false],
                            ['certificates', 'Certificates', 'i-badge', true, false],
                        ] as [$field, $label, $icon, $multiple, $required])
                            <label class="upload @error($multiple ? $field.'.*' : $field) wv-upload--error @enderror" for="wvDoc-{{ $field }}">
                                <input type="file" id="wvDoc-{{ $field }}" name="{{ $multiple ? $field.'[]' : $field }}" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" @if ($multiple) multiple @endif @if ($required) data-required aria-required="true" @endif>
                                <svg class="ic"><use href="#{{ $icon }}"/></svg>
                                <b>{{ $label }}</b>
                                <span class="fname" data-file-name data-empty="{{ $multiple ? 'Up to 5 files' : 'Tap to upload' }}">{{ $multiple ? 'Up to 5 files' : 'Tap to upload' }}</span>
                            </label>
                        @endforeach
                    </div>
                    @foreach (['passport', 'photo', 'cv', 'certificates', 'certificates.*'] as $field)
                        @error($field)<p class="wv-error">{{ $message }}</p>@enderror
                    @endforeach

                    <div class="field wv-notes">
                        <label for="wvNotes">Notes for our team <span class="muted">(optional)</span></label>
                        <textarea class="input" id="wvNotes" name="notes" rows="3" maxlength="2000" placeholder="Job offer details, preferred employer, questions...">{{ old('notes') }}</textarea>
                        @error('notes')<p class="wv-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="check wv-confirm"><input type="checkbox" id="wvAgree" name="confirm" value="1" required @checked(old('confirm'))><span></span><em>I confirm the information is correct and agree to the <a href="{{ route('terms') }}" class="red" target="_blank" rel="noopener">terms &amp; conditions</a>.</em></label>
                    @error('confirm')<p class="wv-error">{{ $message }}</p>@enderror

                    <div class="row row--between wv-actions">
                        <a href="{{ route('work-visa.index') }}" class="btn btn--outline"><svg class="ic"><use href="#i-chev-l"/></svg>Back</a>
                        <button type="submit" class="btn btn--blue btn--lg">Submit Application <svg class="ic"><use href="#i-arrow-r"/></svg></button>
                    </div>
                </form>

                <aside class="summary wv-summary" aria-label="Application summary">
                    <h2 class="card__title">Application Summary</h2>
                    <div class="summary__item">
                        <span class="sum-flag" id="sFlag" aria-hidden="true">{{ $current?->flag() }}</span>
                        <div><b class="h3" id="sTitle">{{ $current ? $current->name().' – Work Visa' : 'Choose a destination' }}</b><br><span class="muted small" id="sCat">{{ $current?->visa_type ?: 'Work visa' }}</span></div>
                    </div>
                    <div class="summary__row"><span>Estimated processing</span><b id="sTime">{{ $current?->processing_time ?: '—' }}</b></div>
                    <div class="summary__row"><span>Job category</span><span id="sJob">{{ $oldCategory !== '' ? $oldCategory : 'Not selected' }}</span></div>
                    <div class="summary__total"><span>Service fee</span><span id="sFee">{{ $current?->formattedFee() ?? 'On request' }}</span></div>
                    <button type="submit" form="wvForm" class="btn btn--blue btn--lg btn--block wv-summary__btn">Submit Application</button>
                    <p class="secure"><svg class="ic"><use href="#i-lock"/></svg>Your documents are stored privately</p>
                    <div class="divider"></div>
                    <p class="small muted">Embassy, medical and other third-party fees are separate. Our team confirms the next steps after reviewing your documents.</p>
                </aside>
            </div>

            <div class="wv-submitbar" id="wvSubmitBar">
                <div class="wv-submitbar__info">
                    <b id="sBarTitle">{{ $current ? $current->flag().' '.$current->name() : 'Choose a destination' }}</b>
                    <span id="sBarMeta">{{ collect([$current?->processing_time, $current?->formattedFee()])->filter()->implode(' · ') ?: 'Work visa application' }}</span>
                </div>
                <button type="submit" form="wvForm" class="btn btn--blue">Submit <svg class="ic"><use href="#i-arrow-r"/></svg></button>
            </div>
        @endif
    </div>
</section>
@endsection

@if ($destinations->isNotEmpty())
@push('scripts')
<script>
(function () {
    'use strict';
    var data = @json($destinationData);
    var other = @json($otherCategory);
    var form = document.getElementById('wvForm');
    var country = document.getElementById('wvCountry');
    var job = document.getElementById('wvJob');
    var $ = function (id) { return document.getElementById(id); };

    function rebuildCategories(d) {
        var keep = job.value;
        var opts = ['<option value="">Select job category</option>'];
        (d ? d.categories : []).forEach(function (c) {
            var o = document.createElement('option'); o.value = c; o.textContent = c;
            opts.push(o.outerHTML);
        });
        var o = document.createElement('option'); o.value = other; o.textContent = 'Other (describe in notes)';
        opts.push(o.outerHTML);
        job.innerHTML = opts.join('');
        if (Array.prototype.some.call(job.options, function (x) { return x.value === keep; })) { job.value = keep; }
    }

    function updateSummary() {
        var d = data[country.value];
        $('sFlag').textContent = d ? d.flag : '';
        $('sTitle').textContent = d ? d.name + ' – Work Visa' : 'Choose a destination';
        $('sCat').textContent = d ? d.visaType : 'Work visa';
        $('sTime').textContent = d ? d.time : '—';
        $('sFee').textContent = d && d.fee ? d.fee : 'On request';
        $('sJob').textContent = job.value || 'Not selected';
        $('sBarTitle').textContent = d ? (d.flag + ' ' + d.name).trim() : 'Choose a destination';
        $('sBarMeta').textContent = d ? [d.time, d.fee].filter(Boolean).join(' · ') : 'Work visa application';
    }

    function filled(names) {
        return names.every(function (n) {
            var el = form.elements.namedItem(n);
            if (!el) { return true; }
            if (el instanceof RadioNodeList) { return !!el.value; }
            if (el.type === 'file') { return el.files && el.files.length > 0; }
            return el.value.trim() !== '' && el.checkValidity();
        });
    }

    function updateSteps() {
        var done = [
            !!country.value,
            !!job.value,
            filled(['first_name', 'last_name', 'date_of_birth', 'gender', 'nationality', 'passport_number', 'passport_expiry', 'email', 'phone', 'current_address']),
            filled(['passport']),
            $('wvAgree').checked
        ];
        var current = done.indexOf(false);
        if (current === -1) { current = 5; }
        document.querySelectorAll('#wvSteps .step').forEach(function (s, i) {
            s.classList.toggle('is-done', i < current);
            s.classList.toggle('is-current', i === current);
            if (i === current) { s.setAttribute('aria-current', 'step'); } else { s.removeAttribute('aria-current'); }
        });
    }

    country.addEventListener('change', function () { rebuildCategories(data[country.value]); updateSummary(); updateSteps(); });
    job.addEventListener('change', function () { updateSummary(); updateSteps(); });
    form.addEventListener('input', updateSteps);
    form.addEventListener('change', updateSteps);

    form.querySelectorAll('input[type=file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var tooBig = Array.prototype.some.call(input.files, function (f) { return f.size > 5 * 1024 * 1024; });
            var box = input.closest('.upload');
            if (tooBig) {
                input.value = '';
                box.classList.add('wv-upload--error');
                var label = box.querySelector('[data-file-name]');
                if (label) { label.textContent = 'Each file must be 5 MB or smaller'; }
            } else {
                box.classList.remove('wv-upload--error');
            }
            box.classList.toggle('has-file', input.files.length > 0);
        });
    });

    form.addEventListener('submit', function (e) {
        var passport = form.elements.namedItem('passport');
        if (passport && !passport.files.length) {
            e.preventDefault();
            var box = passport.closest('.upload');
            box.classList.add('wv-upload--error');
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        form.querySelectorAll('button[type=submit]').forEach(function (b) { b.disabled = true; });
        document.querySelectorAll('button[form=wvForm]').forEach(function (b) { b.disabled = true; });
    });

    var errors = $('wvErrors');
    if (errors) { errors.focus({ preventScroll: false }); }
    updateSummary();
    updateSteps();
})();
</script>
@endpush
@endif
