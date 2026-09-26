{{-- One service-specific enquiry field built from config/enquiries.php. --}}
@php
    $inputId = 'enq-'.$key;
    $type = $field['type'];
    $required = $field['required'] ?? false;
    $error = $errors->first($key);
@endphp
<div @class(['field', 'span-all' => ($field['wide'] ?? false) || $type === 'textarea', 'field--error' => $error])>
    <label for="{{ $inputId }}">{{ $field['label'] }}@if ($required) <em>*</em>@endif</label>
    @if ($type === 'select')
        <select class="input" id="{{ $inputId }}" name="{{ $key }}" @required($required) @if ($error) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif>
            <option value="">{{ $required ? 'Select' : 'No preference' }}</option>
            @foreach ($field['options'] as $option)
                <option value="{{ $option }}" @selected((string) $value === $option)>{{ $option }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea class="input" id="{{ $inputId }}" name="{{ $key }}" maxlength="{{ $field['max'] ?? 3000 }}" @required($required)>{{ $value }}</textarea>
    @else
        <input
            class="input"
            id="{{ $inputId }}"
            name="{{ $key }}"
            type="{{ $type === 'number' ? 'number' : $type }}"
            value="{{ $value }}"
            @if ($type === 'number') min="{{ $field['min'] ?? 0 }}" max="{{ $field['max'] ?? 999 }}" step="1" @endif
            @if ($type === 'text' && isset($field['max'])) maxlength="{{ $field['max'] }}" @endif
            @if ($type === 'date' && ($field['future'] ?? false)) min="{{ now()->toDateString() }}" @endif
            @if ($type === 'month' && ($field['future'] ?? false)) min="{{ now()->format('Y-m') }}" @endif
            @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
            @isset($field['inputmode']) inputmode="{{ $field['inputmode'] }}" @endisset
            autocomplete="{{ $field['autocomplete'] ?? 'off' }}"
            @required($required)
            @if ($error) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
        >
    @endif
    @isset($field['hint'])<span class="hint">{{ $field['hint'] }}</span>@endisset
    @if ($error)<span class="field__error" id="{{ $inputId }}-error">{{ $error }}</span>@endif
</div>
