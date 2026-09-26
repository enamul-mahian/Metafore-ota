{{--
    Presentation helpers for the flight search page: search bar niceties,
    booking stepper, result filters/sorting, sticky fare summary and the
    phone "Continue" bar. Booking logic itself lives in resources/js/app.js;
    this script only reads what app.js renders and the events it dispatches
    (flight:results, flight:selected, flight:fare-updated).
--}}
<script>
(() => {
    const page = document.querySelector('[data-flight-page]');
    const form = page?.querySelector('[data-flight-search-form]');

    if (!page || !form) {
        return;
    }

    const $ = (selector, root = page) => root.querySelector(selector);
    const $$ = (selector, root = page) => Array.from(root.querySelectorAll(selector));
    const results = $('[data-flight-results]');

    /* ---------- Formatting ---------- */
    const money = (amount, currency) => {
        const value = Number(amount);

        if (!Number.isFinite(value)) {
            return [currency, amount].filter(Boolean).join(' ');
        }

        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: currency || 'USD',
                currencyDisplay: 'code',
                maximumFractionDigits: 0,
            }).format(value);
        } catch (error) {
            return `${currency || ''} ${Math.round(value)}`.trim();
        }
    };

    const durationMinutes = (value) => {
        const match = typeof value === 'string'
            ? value.match(/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?)?$/)
            : null;

        if (!match) {
            return null;
        }

        return (Number(match[1] || 0) * 1440) + (Number(match[2] || 0) * 60) + Number(match[3] || 0);
    };

    const durationLabel = (minutes) => {
        if (!Number.isFinite(minutes)) {
            return '';
        }

        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;

        return [hours ? `${hours}h` : '', rest ? `${rest}m` : ''].filter(Boolean).join(' ');
    };

    const placeCode = (place) => place?.iata_code || place?.name || '';

    /* ---------- Search bar ---------- */
    const origin = $('#flight-origin');
    const destination = $('#flight-destination');

    $('[data-flight-swap]')?.addEventListener('click', () => {
        [origin.value, destination.value] = [destination.value, origin.value];
    });

    const pax = $('[data-flight-pax]');
    const paxInputs = ['adults', 'children', 'infants'].map((name) => form.elements.namedItem(name));

    const updatePaxSummary = () => {
        const total = paxInputs.reduce((sum, input) => sum + Math.max(0, Number(input?.value || 0)), 0);
        $('[data-flight-pax-summary]').textContent = `${total} Traveller${total === 1 ? '' : 's'}`;
    };

    paxInputs.forEach((input) => input?.addEventListener('input', updatePaxSummary));
    updatePaxSummary();

    $('[data-flight-pax-done]')?.addEventListener('click', () => {
        pax.open = false;
    });

    document.addEventListener('click', (event) => {
        if (pax.open && !pax.contains(event.target)) {
            pax.open = false;
        }
    });

    /* Passenger errors live inside the dropdown: open it when one appears. */
    new MutationObserver(() => {
        if ($$('[data-error-for]', pax).some((element) => element.textContent.trim() !== '')) {
            pax.open = true;
        }
    }).observe(pax, { subtree: true, childList: true, characterData: true });

    /* A new search must not be blocked by empty traveller fields of an earlier selection. */
    $('[data-flight-submit]')?.addEventListener('click', (event) => {
        event.currentTarget.formNoValidate = Boolean(results.querySelector('[data-flight-traveler-review]'));
    });

    /* ---------- Stage, stepper, summary visibility ---------- */
    const stages = ['search', 'select', 'travellers', 'review', 'payment', 'confirmation'];

    const currentStage = () => {
        if (results.hidden || !results.querySelector('.flight-offer-cards')) {
            return 'search';
        }

        if (results.querySelector('.flight-order-payment-panel.is-confirmed')) {
            return 'confirmation';
        }

        if (results.querySelector('.flight-order-payment-panel')) {
            return 'payment';
        }

        if (results.querySelector('[data-flight-booking-draft-review-status], .flight-booking-confirmation-intent-panel, .flight-order-execution-panel')) {
            return 'review';
        }

        if (results.querySelector('[data-flight-traveler-review]')) {
            return 'travellers';
        }

        return 'select';
    };

    const summary = $('[data-flight-summary]');
    const sticky = $('[data-flight-sticky]');
    let lastStage = 'search';

    const syncStage = () => {
        const stage = currentStage();
        const index = stages.indexOf(stage);
        const hasSelection = summary.dataset.ready === 'true';
        const checkout = index >= 2;

        page.dataset.flightStage = stage;
        $('[data-flight-steps-card]').hidden = stage === 'search';
        $('[data-flight-intro]').hidden = stage !== 'search';
        $('[data-flight-change]').hidden = stage !== 'travellers';
        summary.hidden = !(checkout && hasSelection);
        sticky.hidden = !(checkout && hasSelection) || stage === 'confirmation';

        if (!checkout) {
            delete page.dataset.flightShowOffers;
        }

        $$('[data-flight-step]').forEach((step, stepIndex) => {
            step.classList.toggle('is-done', stepIndex < index || stage === 'confirmation');
            step.classList.toggle('is-current', stepIndex === index && stage !== 'confirmation');

            if (stepIndex === index) {
                step.setAttribute('aria-current', 'step');
            } else {
                step.removeAttribute('aria-current');
            }
        });

        if (stage !== lastStage && index > stages.indexOf(lastStage) && index >= 2) {
            $('[data-flight-steps-card]').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        lastStage = stage;
    };

    new MutationObserver(syncStage).observe(results, { subtree: true, childList: true, attributes: true, attributeFilter: ['hidden', 'class'] });

    $('[data-flight-change-button]')?.addEventListener('click', (event) => {
        const showing = page.dataset.flightShowOffers === 'true';

        if (showing) {
            delete page.dataset.flightShowOffers;
        } else {
            page.dataset.flightShowOffers = 'true';
            results.querySelector('.flight-offer-cards')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        event.currentTarget.lastChild.textContent = showing ? 'Change flight' : 'Hide other flights';
    });

    /* ---------- Sticky fare summary ---------- */
    const setTotal = (amount, currency) => {
        const label = money(amount, currency);

        $('[data-flight-summary-total]').textContent = label;
        $('[data-flight-sticky-total]').textContent = label;
    };

    document.addEventListener('flight:selected', (event) => {
        const offer = event.detail?.offer || {};
        const criteria = event.detail?.criteria || {};
        const slices = Array.isArray(offer.slices) ? offer.slices : [];
        const firstSlice = slices[0] || {};
        const firstSegment = Array.isArray(firstSlice.segments) ? firstSlice.segments[0] : null;
        const airline = offer.owner?.name || 'Selected flight';

        $('[data-flight-summary-mark]').textContent = airline.trim().charAt(0).toUpperCase() || 'F';
        $('[data-flight-summary-airline]').textContent = airline;

        const route = $('[data-flight-summary-route]');
        route.textContent = [placeCode(firstSlice.origin), placeCode(firstSlice.destination)].filter(Boolean).join(' → ');

        if (slices.length > 1) {
            route.textContent += ' · Round trip';
        }

        const departure = firstSegment?.departing_at ? new Date(firstSegment.departing_at) : null;
        $('[data-flight-summary-when]').textContent = departure && !Number.isNaN(departure.getTime())
            ? new Intl.DateTimeFormat(undefined, { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(departure)
            : '';

        const travellers = [['adults', 'Adult'], ['children', 'Child'], ['infants', 'Infant']]
            .map(([key, label]) => [Number(criteria[key] || 0), label])
            .filter(([count]) => count > 0)
            .map(([count, label]) => `${count} ${label}${count === 1 ? '' : (label === 'Child' ? 'ren' : 's')}`);

        $('[data-flight-summary-travellers]').textContent = travellers.join(', ') || '—';
        setTotal(offer.total_amount, offer.total_currency);
        summary.dataset.ready = 'true';
        syncStage();
    });

    document.addEventListener('flight:fare-updated', (event) => {
        if (event.detail?.amount && event.detail?.currency) {
            setTotal(event.detail.amount, event.detail.currency);
        }
    });

    /* Phone bar: bring the next action into view (never presses it for the customer). */
    $('[data-flight-sticky-continue]')?.addEventListener('click', () => {
        const target = $$(
            '.flight-order-payment-panel button:not(:disabled), .flight-order-execution-panel button:not(:disabled), '
            + '.flight-booking-confirmation-intent-panel button:not(:disabled), .flight-booking-continue:not(:disabled), '
            + '.flight-traveler-input:invalid',
            results,
        ).pop();

        (target || results.lastElementChild)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target?.focus({ preventScroll: true });
    });

    /* ---------- Filters and sorting ---------- */
    const filterState = { stops: new Set(), airlines: new Set(), maxPrice: null, slots: new Set(), sort: 'price' };
    let offerFacts = [];

    const factsFor = (offer, index) => {
        const slices = Array.isArray(offer?.slices) ? offer.slices : [];
        const segments = slices.flatMap((slice) => (Array.isArray(slice?.segments) ? slice.segments : []));
        const stops = slices.reduce((max, slice) => Math.max(max, Math.max(0, (slice?.segments?.length || 1) - 1)), 0);
        const minutes = slices.reduce((sum, slice) => sum + (durationMinutes(slice?.duration) ?? 0), 0);
        const departingAt = segments[0]?.departing_at || '';
        const hour = /T(\d{2})/.exec(departingAt)?.[1];

        return {
            index,
            price: Number(offer?.total_amount),
            currency: offer?.total_currency || '',
            airline: offer?.owner?.name || 'Other',
            stops: Math.min(stops, 2),
            minutes: minutes || Number.POSITIVE_INFINITY,
            departingAt,
            slot: hour === undefined ? null : Math.floor(Number(hour) / 6),
        };
    };

    const cardsBox = () => results.querySelector('.flight-offer-cards');
    const cardFor = (facts) => cardsBox()?.querySelector(`[data-offer-index="${facts.index}"]`);

    const matches = (facts) => (filterState.stops.size === 0 || filterState.stops.has(facts.stops))
        && (filterState.airlines.size === 0 || filterState.airlines.has(facts.airline))
        && (filterState.maxPrice === null || !Number.isFinite(facts.price) || facts.price <= filterState.maxPrice)
        && (filterState.slots.size === 0 || filterState.slots.has(facts.slot));

    const sorters = {
        price: (a, b) => (a.price || 0) - (b.price || 0),
        duration: (a, b) => a.minutes - b.minutes,
        departure: (a, b) => a.departingAt.localeCompare(b.departingAt),
    };

    const applyFilters = () => {
        const box = cardsBox();

        if (!box) {
            return;
        }

        let shown = 0;

        [...offerFacts].sort(sorters[filterState.sort]).forEach((facts) => {
            const card = cardFor(facts);

            if (!card) {
                return;
            }

            card.hidden = !matches(facts);
            shown += card.hidden ? 0 : 1;
            box.append(card);
        });

        $$('[data-flight-shown-count]').forEach((element) => {
            element.textContent = String(shown);
        });

        $('[data-flight-no-match]').hidden = shown > 0 || offerFacts.length === 0;
    };

    const checkbox = (label, count, onChange) => {
        const wrapper = document.createElement('label');
        const input = document.createElement('input');
        const counter = document.createElement('em');

        wrapper.className = 'opt';
        input.type = 'checkbox';
        input.addEventListener('change', () => {
            onChange(input.checked);
            applyFilters();
        });
        counter.textContent = String(count);
        wrapper.append(input, ` ${label} `, counter);

        return wrapper;
    };

    const buildFilters = () => {
        const stopsBox = $('[data-flight-filter-stops]');
        const airlinesBox = $('[data-flight-filter-airlines]');
        const stopLabels = ['Non-stop', '1 Stop', '2+ Stops'];

        stopsBox.replaceChildren();
        airlinesBox.replaceChildren();

        [0, 1, 2].forEach((stops) => {
            const count = offerFacts.filter((facts) => facts.stops === stops).length;

            if (count > 0) {
                stopsBox.append(checkbox(stopLabels[stops], count, (on) => {
                    on ? filterState.stops.add(stops) : filterState.stops.delete(stops);
                }));
            }
        });

        [...new Set(offerFacts.map((facts) => facts.airline))].sort().forEach((airline) => {
            const count = offerFacts.filter((facts) => facts.airline === airline).length;

            airlinesBox.append(checkbox(airline, count, (on) => {
                on ? filterState.airlines.add(airline) : filterState.airlines.delete(airline);
            }));
        });

        const prices = offerFacts.map((facts) => facts.price).filter(Number.isFinite);
        const currency = offerFacts[0]?.currency || '';
        const range = $('[data-flight-filter-price]');
        const sameCurrency = offerFacts.every((facts) => facts.currency === currency);

        $('[data-flight-filter-price-group]').hidden = prices.length < 2 || !sameCurrency;

        if (prices.length > 0) {
            const min = Math.floor(Math.min(...prices));
            const max = Math.ceil(Math.max(...prices));

            range.min = String(min);
            range.max = String(max);
            range.step = String(Math.max(1, Math.round((max - min) / 50)));
            range.value = String(max);
            $('[data-flight-filter-price-min]').textContent = money(min, currency);
            $('[data-flight-filter-price-value]').textContent = money(max, currency);
        }

        const best = (key) => [...offerFacts].sort(sorters[key])[0];
        const cheapest = best('price');
        const fastest = best('duration');
        const earliest = best('departure');

        $('[data-flight-best="price"]').textContent = cheapest ? money(cheapest.price, cheapest.currency) : '';
        $('[data-flight-best="duration"]').textContent = fastest ? durationLabel(fastest.minutes) : '';
        $('[data-flight-best="departure"]').textContent = earliest?.departingAt ? earliest.departingAt.slice(11, 16) : '';
        $('[data-flight-total-count]').textContent = String(offerFacts.length);
    };

    const resetFilters = () => {
        filterState.stops.clear();
        filterState.airlines.clear();
        filterState.slots.clear();
        filterState.maxPrice = null;
        $$('[data-flight-filters] input[type="checkbox"]').forEach((input) => {
            input.checked = false;
        });
        $$('[data-flight-filter-slots] .timeslot').forEach((button) => {
            button.classList.remove('is-active');
            button.setAttribute('aria-pressed', 'false');
        });
        const range = $('[data-flight-filter-price]');
        range.value = range.max;
        $('[data-flight-filter-price-value]').textContent = money(range.max, offerFacts[0]?.currency || '');
    };

    $('[data-flight-filter-price]')?.addEventListener('input', (event) => {
        const value = Number(event.target.value);

        filterState.maxPrice = value >= Number(event.target.max) ? null : value;
        $('[data-flight-filter-price-value]').textContent = money(value, offerFacts[0]?.currency || '');
        applyFilters();
    });

    $$('[data-flight-filter-slots] .timeslot').forEach((button) => {
        button.addEventListener('click', () => {
            const slot = Number(button.dataset.slot);
            const active = !filterState.slots.has(slot);

            active ? filterState.slots.add(slot) : filterState.slots.delete(slot);
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
            applyFilters();
        });
    });

    $$('[data-flight-filter-reset]').forEach((button) => {
        button.addEventListener('click', () => {
            resetFilters();
            applyFilters();
        });
    });

    $$('[data-flight-sort]').forEach((button) => {
        button.addEventListener('click', () => {
            filterState.sort = button.dataset.flightSort;
            $$('[data-flight-sort]').forEach((other) => {
                other.classList.toggle('is-active', other === button);
                other.setAttribute('aria-pressed', String(other === button));
            });
            applyFilters();
        });
    });

    document.addEventListener('flight:results', (event) => {
        const offers = Array.isArray(event.detail?.offers) ? event.detail.offers : [];

        offerFacts = offers.map(factsFor);
        buildFilters();
        resetFilters();
        filterState.sort = 'price';
        $$('[data-flight-sort]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.flightSort === 'price');
            button.setAttribute('aria-pressed', String(button.dataset.flightSort === 'price'));
        });

        const firstSlice = offers[0]?.slices?.[0];
        $('[data-flight-results-title]').textContent = firstSlice
            ? `Flights from ${placeCode(firstSlice.origin)} to ${placeCode(firstSlice.destination)}`
            : 'Available flights';

        summary.dataset.ready = 'false';
        applyFilters();
        syncStage();
    });

    syncStage();
})();
</script>
