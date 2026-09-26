{{-- Homepage flight search: live search when the flight provider is set up, otherwise a fare-quote request. --}}
@php
    $action = $isLive ? route('flights.index') : $enquiryUrl;
    $fromName = $isLive ? 'origin' : 'from';
    $toName = $isLive ? 'destination' : 'to';
    $travellersName = $isLive ? 'adults' : 'travellers';
    $cabinName = $isLive ? 'cabin_class' : 'cabin';
    $cabins = $isLive
        ? ['economy' => 'Economy', 'premium_economy' => 'Premium Economy', 'business' => 'Business', 'first' => 'First Class']
        : ['Economy' => 'Economy', 'Premium economy' => 'Premium Economy', 'Business' => 'Business', 'First' => 'First Class'];
@endphp
@if ($action)
    <form method="GET" action="{{ $action }}" data-trip-toggle data-track="search" aria-label="Flight search">
        <fieldset class="search__trip">
            <legend class="sr">Trip type</legend>
            <label class="radio"><input type="radio" name="trip_type" value="round_trip" checked><span></span>Round Trip</label>
            <label class="radio"><input type="radio" name="trip_type" value="one_way"><span></span>One Way</label>
        </fieldset>
        <div class="search__grid">
            <div class="fld fld--wide">
                <label for="flight-from">From</label>
                <div class="fld__box"><svg class="ic"><use href="#i-plane"/></svg>
                    @if ($isLive)
                        <input id="flight-from" name="origin" placeholder="Airport code, e.g. DAC" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" autocomplete="off" autocapitalize="characters" spellcheck="false">
                    @else
                        <input id="flight-from" name="from" placeholder="Dhaka (DAC)" required maxlength="120" autocomplete="off">
                    @endif
                </div>
            </div>
            <button type="button" class="swap" data-swap="{{ $fromName }},{{ $toName }}" aria-label="Swap from and to"><svg class="ic"><use href="#i-swap"/></svg></button>
            <div class="fld fld--wide">
                <label for="flight-to">To</label>
                <div class="fld__box"><svg class="ic"><use href="#i-pin"/></svg>
                    @if ($isLive)
                        <input id="flight-to" name="destination" placeholder="Airport code, e.g. DXB" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" autocomplete="off" autocapitalize="characters" spellcheck="false">
                    @else
                        <input id="flight-to" name="to" placeholder="Dubai (DXB)" required maxlength="120" autocomplete="off">
                    @endif
                </div>
            </div>
            <div class="fld fld--half">
                <label for="flight-depart">Departure</label>
                <div class="fld__box"><svg class="ic"><use href="#i-cal"/></svg><input id="flight-depart" type="date" name="departure_date" min="{{ $today }}" required></div>
            </div>
            <div class="fld fld--half" data-return-field>
                <label for="flight-return">Return</label>
                <div class="fld__box"><svg class="ic"><use href="#i-cal"/></svg><input id="flight-return" type="date" name="return_date" min="{{ $today }}"></div>
            </div>
            <div class="fld fld--half">
                <label for="flight-travellers">Travellers</label>
                <div class="fld__box"><svg class="ic"><use href="#i-users"/></svg>
                    <select id="flight-travellers" name="{{ $travellersName }}">
                        @for ($i = 1; $i <= 9; $i++)
                            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'Adult' : 'Adults' }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="fld fld--half">
                <label for="flight-cabin">Class</label>
                <div class="fld__box">
                    <select id="flight-cabin" name="{{ $cabinName }}">
                        @foreach ($cabins as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="search__go"><svg class="ic"><use href="#i-search"/></svg>{{ $isLive ? 'Search Flights' : 'Request Fare Quote' }}</button>
        </div>
        @unless ($isLive)
            <p class="search__note">Live flight search is not connected yet. Send your route and our ticketing team replies with fares.</p>
        @endunless
    </form>
@endif
