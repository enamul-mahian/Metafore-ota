{{-- Tour search form. $criteria (optional) pre-fills a repeat search. --}}
@php
    $criteria = $criteria ?? [];
    $stacked = $stacked ?? false;
    $errors ??= new \Illuminate\Support\ViewErrorBag;
@endphp
<form method="POST" action="{{ route('tours.search') }}" @class(['form-grid', 'form-grid--4' => ! $stacked, 'form-grid--1' => $stacked]) data-track="search">
    @csrf
    <div @class(['field', 'span-2' => ! $stacked, 'field--error' => $errors->has('destination')])>
        <label for="t-destination">Destination</label>
        <input class="input" id="t-destination" type="text" name="destination" value="{{ old('destination', $criteria['destination'] ?? '') }}" maxlength="120" autocomplete="off" placeholder="City or country" required>
        @error('destination')<span class="field__error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'field--error' => $errors->has('travel_date')])>
        <label for="t-date">Preferred date</label>
        <input class="input" id="t-date" type="date" name="travel_date" value="{{ old('travel_date', $criteria['travel_date'] ?? '') }}" min="{{ now()->toDateString() }}">
        @error('travel_date')<span class="field__error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'field--error' => $errors->has('travelers')])>
        <label for="t-travelers">Travelers</label>
        <select class="input" id="t-travelers" name="travelers" required>
            @for ($travelers = 1; $travelers <= 12; $travelers++)
                <option value="{{ $travelers }}" @selected((int) old('travelers', $criteria['travelers'] ?? 2) === $travelers)>{{ $travelers }}</option>
            @endfor
        </select>
    </div>
    <div @class(['field', 'span-all' => ! $stacked])>
        <button type="submit" class="btn btn--blue btn--block"><svg class="ic"><use href="#i-search"/></svg>Search Tours</button>
    </div>
</form>
