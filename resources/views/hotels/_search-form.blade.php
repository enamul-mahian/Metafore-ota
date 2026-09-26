{{-- Hotel search form. $criteria (optional) pre-fills a repeat search. --}}
@php
    $criteria = $criteria ?? [];
    $stacked = $stacked ?? false;
    $errors ??= new \Illuminate\Support\ViewErrorBag;
@endphp
<form method="POST" action="{{ route('hotels.search') }}" @class(['form-grid', 'form-grid--4' => ! $stacked, 'form-grid--1' => $stacked]) data-track="search">
    @csrf
    <div @class(['field', 'span-2' => ! $stacked, 'field--error' => $errors->has('destination')])>
        <label for="h-destination">Destination</label>
        <input class="input" id="h-destination" type="text" name="destination" value="{{ old('destination', $criteria['destination'] ?? '') }}" maxlength="120" autocomplete="off" placeholder="City, area or landmark" required>
        @error('destination')<span class="field__error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'field--error' => $errors->has('check_in')])>
        <label for="h-check-in">Check in</label>
        <input class="input" id="h-check-in" type="date" name="check_in" value="{{ old('check_in', $criteria['check_in'] ?? '') }}" min="{{ now()->toDateString() }}" required>
        @error('check_in')<span class="field__error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'field--error' => $errors->has('check_out')])>
        <label for="h-check-out">Check out</label>
        <input class="input" id="h-check-out" type="date" name="check_out" value="{{ old('check_out', $criteria['check_out'] ?? '') }}" min="{{ now()->addDay()->toDateString() }}" required>
        @error('check_out')<span class="field__error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'field--error' => $errors->has('adults')])>
        <label for="h-adults">Adults</label>
        <select class="input" id="h-adults" name="adults" required>
            @for ($adults = 1; $adults <= 9; $adults++)
                <option value="{{ $adults }}" @selected((int) old('adults', $criteria['adults'] ?? 2) === $adults)>{{ $adults }}</option>
            @endfor
        </select>
    </div>
    <div @class(['field', 'field--error' => $errors->has('rooms')])>
        <label for="h-rooms">Rooms</label>
        <select class="input" id="h-rooms" name="rooms" required>
            @for ($rooms = 1; $rooms <= 5; $rooms++)
                <option value="{{ $rooms }}" @selected((int) old('rooms', $criteria['rooms'] ?? 1) === $rooms)>{{ $rooms }}</option>
            @endfor
        </select>
    </div>
    <div @class(['field', 'span-2' => ! $stacked])>
        @unless ($stacked)<span aria-hidden="true">&nbsp;</span>@endunless
        <button type="submit" class="btn btn--blue btn--block"><svg class="ic"><use href="#i-search"/></svg>Search Hotels</button>
    </div>
</form>
