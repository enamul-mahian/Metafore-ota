{{-- Homepage tab for a service booked through a request form (and, when connected, a live search). --}}
@php
    $definition = config('enquiries.services.'.$serviceKey);
    $fields = collect($definition['fields'] ?? [])
        ->reject(fn (array $field): bool => $field['type'] === 'textarea')
        ->take(5);
    $icons = ['date' => 'cal', 'month' => 'cal', 'time' => 'clock', 'number' => 'users', 'select' => 'grid', 'text' => 'pin'];
@endphp
@if ($enquiryUrl && $definition)
    <form method="GET" action="{{ $enquiryUrl }}" aria-label="{{ $serviceLabel }} request">
        <div class="search__grid">
            @foreach ($fields as $name => $field)
                @php $id = 'home-'.$serviceKey.'-'.$name; @endphp
                <div @class(['fld', 'fld--half' => in_array($field['type'], ['date', 'month', 'time', 'number', 'select'], true), 'fld--wide' => $field['type'] === 'text'])>
                    <label for="{{ $id }}">{{ $field['label'] }}</label>
                    <div class="fld__box"><svg class="ic"><use href="#i-{{ $icons[$field['type']] ?? 'pin' }}"/></svg>
                        @if ($field['type'] === 'select')
                            <select id="{{ $id }}" name="{{ $name }}">
                                @foreach ($field['options'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="{{ $id }}" name="{{ $name }}" type="{{ $field['type'] }}"
                                @if ($field['type'] === 'number') inputmode="numeric" min="{{ $field['min'] ?? 1 }}" max="{{ $field['max'] ?? 99 }}" value="{{ $field['default'] ?? '' }}" @endif
                                @if (in_array($field['type'], ['date', 'month'], true) && ! empty($field['future'])) min="{{ $field['type'] === 'month' ? substr($today, 0, 7) : $today }}" @endif
                                @if (! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                                @if ($field['type'] === 'text') maxlength="{{ $field['max'] ?? 120 }}" autocomplete="off" @endif>
                        @endif
                    </div>
                </div>
            @endforeach
            <button type="submit" class="search__go"><svg class="ic"><use href="#i-send"/></svg>Request a Quote</button>
        </div>
        @if ($isLive && $liveUrl)
            <p class="search__note"><a href="{{ $liveUrl }}" class="link">Or search {{ strtolower($serviceLabel) }} with live availability</a></p>
        @else
            <p class="search__note">{{ $definition['intro'] }}</p>
        @endif
    </form>
@endif
