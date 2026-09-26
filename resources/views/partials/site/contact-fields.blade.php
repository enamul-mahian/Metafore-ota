{{-- Name / email / phone inputs shared by the enquiry and contact forms. --}}
@php
    $user = auth()->user();
@endphp
@foreach ([
    'name' => ['Full name', 'text', 'name', 'Your full name', true, null],
    'email' => ['Email address', 'email', 'email', 'you@example.com', true, 'email'],
    'phone' => ['Phone / WhatsApp', 'tel', 'tel', 'Mobile or WhatsApp number', false, 'tel'],
] as $key => [$label, $type, $autocomplete, $placeholder, $required, $inputmode])
    @php
        $error = $errors->first($key);
        $default = match ($key) {
            'name' => $user?->name,
            'email' => $user?->email,
            default => null,
        };
    @endphp
    <div @class(['field', 'span-all' => $key === 'phone' && ($phoneFull ?? false), 'field--error' => $error])>
        <label for="c-{{ $key }}">{{ $label }}@if ($required) <em>*</em>@endif</label>
        <input class="input" id="c-{{ $key }}" name="{{ $key }}" type="{{ $type }}" value="{{ old($key, $default) }}"
            autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}" maxlength="{{ $key === 'phone' ? 32 : ($key === 'name' ? 120 : 255) }}"
            @if ($inputmode) inputmode="{{ $inputmode }}" @endif @required($required)
            @if ($error) aria-invalid="true" aria-describedby="c-{{ $key }}-error" @endif>
        @if ($error)<span class="field__error" id="c-{{ $key }}-error">{{ $error }}</span>@endif
    </div>
@endforeach
<div class="hp-field" aria-hidden="true">
    <label for="c-website">Leave this field empty</label>
    <input id="c-website" name="website" type="text" value="" tabindex="-1" autocomplete="off">
</div>
