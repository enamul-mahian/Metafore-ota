document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-flight-search-form]');

    if (!form) {
        return;
    }

    const submitButton = form.querySelector('[data-flight-submit]');
    const statusBox = form.querySelector('[data-flight-status]');
    const resultsBox = form.querySelector('[data-flight-results]');
    const returnInput = form.querySelector('[data-return-date]');
    const departureInput = form.querySelector('[data-departure-date]');
    const airportInputs = form.querySelectorAll('[data-airport-code]');
    const tripTypeInputs = form.querySelectorAll('input[name="trip_type"]');

    const clearErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((element) => {
            element.textContent = '';
        });
    };

    const showStatus = (message, type = 'info') => {
        statusBox.hidden = false;
        statusBox.textContent = message;
        statusBox.className = `flight-status flight-status-${type}`;
    };

    const hideStatus = () => {
        statusBox.hidden = true;
        statusBox.textContent = '';
        statusBox.className = 'flight-status';
    };

    const showValidationErrors = (errors) => {
        Object.entries(errors).forEach(([field, messages]) => {
            const target = form.querySelector(
                `[data-error-for="${field}"]`
            );

            if (target) {
                target.textContent = Array.isArray(messages)
                    ? messages[0]
                    : messages;
            }
        });

        const firstMessage = Object.values(errors)
            .flat()
            .find(Boolean);

        showStatus(
            firstMessage || 'Please review the highlighted search details.',
            'error'
        );
    };

    const updateTripType = () => {
        const selected = form.querySelector(
            'input[name="trip_type"]:checked'
        );

        const roundTrip = selected?.value === 'round_trip';

        returnInput.disabled = !roundTrip;
        returnInput.required = roundTrip;

        if (!roundTrip) {
            returnInput.value = '';
        }
    };

    const updateReturnMinimum = () => {
        if (!departureInput.value) {
            return;
        }

        const departure = new Date(
            `${departureInput.value}T00:00:00`
        );

        departure.setDate(departure.getDate() + 1);

        const year = departure.getFullYear();
        const month = String(departure.getMonth() + 1).padStart(2, '0');
        const day = String(departure.getDate()).padStart(2, '0');

        returnInput.min = `${year}-${month}-${day}`;

        if (
            returnInput.value &&
            returnInput.value < returnInput.min
        ) {
            returnInput.value = '';
        }
    };

    airportInputs.forEach((input) => {
        input.addEventListener('input', () => {
            input.value = input.value
                .toUpperCase()
                .replace(/[^A-Z]/g, '')
                .slice(0, 3);
        });
    });

    tripTypeInputs.forEach((input) => {
        input.addEventListener('change', updateTripType);
    });

    departureInput.addEventListener(
        'change',
        updateReturnMinimum
    );

    updateTripType();
    updateReturnMinimum();

    const createFlightElement = (
        tagName,
        className = '',
        text = null
    ) => {
        const element = document.createElement(tagName);

        if (className) {
            element.className = className;
        }

        if (text !== null && text !== undefined) {
            element.textContent = String(text);
        }

        return element;
    };

    const flightPlaceCode = (place) => {
        return place?.iata_code || place?.name || '—';
    };

    const flightCarrierName = (segment, offer) => {
        return (
            segment?.marketing_carrier?.name ||
            segment?.operating_carrier?.name ||
            offer?.owner?.name ||
            'Carrier unavailable'
        );
    };

    const formatFlightMoney = (amount, currency) => {
        const numericAmount = Number(amount);

        if (!Number.isFinite(numericAmount)) {
            return [currency, amount]
                .filter(Boolean)
                .join(' ');
        }

        if (!currency) {
            return numericAmount.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency,
                currencyDisplay: 'code',
            }).format(numericAmount);
        } catch (error) {
            return `${currency} ${numericAmount.toFixed(2)}`;
        }
    };

    const formatFlightDateTime = (value) => {
        if (!value) {
            return 'Time unavailable';
        }

        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat(undefined, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(parsed);
    };

    const formatFlightDuration = (value) => {
        if (!value || typeof value !== 'string') {
            return '';
        }

        const match = value.match(
            /^PT(?:(\d+)H)?(?:(\d+)M)?$/
        );

        if (!match) {
            return value;
        }

        const hours = Number(match[1] || 0);
        const minutes = Number(match[2] || 0);
        const parts = [];

        if (hours > 0) {
            parts.push(`${hours}h`);
        }

        if (minutes > 0) {
            parts.push(`${minutes}m`);
        }

        return parts.join(' ') || value;
    };

    const renderFlightSegment = (
        segment,
        offer
    ) => {
        const row = createFlightElement(
            'div',
            'flight-offer-segment'
        );

        const carrier = createFlightElement(
            'div',
            'flight-segment-carrier'
        );

        const carrierName = flightCarrierName(
            segment,
            offer
        );

        const carrierMark = createFlightElement(
            'span',
            'flight-carrier-mark',
            carrierName
                .trim()
                .charAt(0)
                .toUpperCase() || 'F'
        );

        const carrierText = createFlightElement(
            'div',
            'flight-carrier-text'
        );

        carrierText.append(
            createFlightElement(
                'strong',
                '',
                carrierName
            )
        );

        const flightNumber =
            segment?.marketing_carrier_flight_number ||
            segment?.operating_carrier_flight_number;

        if (flightNumber) {
            carrierText.append(
                createFlightElement(
                    'span',
                    '',
                    `Flight ${flightNumber}`
                )
            );
        }

        carrier.append(
            carrierMark,
            carrierText
        );

        const departure = createFlightElement(
            'div',
            'flight-segment-point'
        );

        departure.append(
            createFlightElement(
                'strong',
                '',
                flightPlaceCode(segment?.origin)
            ),
            createFlightElement(
                'span',
                '',
                formatFlightDateTime(
                    segment?.departing_at
                )
            )
        );

        const journey = createFlightElement(
            'div',
            'flight-segment-journey'
        );

        journey.append(
            createFlightElement(
                'span',
                'flight-segment-duration',
                formatFlightDuration(
                    segment?.duration
                )
            ),
            createFlightElement(
                'span',
                'flight-segment-line',
                '→'
            )
        );

        const arrival = createFlightElement(
            'div',
            'flight-segment-point flight-segment-point-arrival'
        );

        arrival.append(
            createFlightElement(
                'strong',
                '',
                flightPlaceCode(
                    segment?.destination
                )
            ),
            createFlightElement(
                'span',
                '',
                formatFlightDateTime(
                    segment?.arriving_at
                )
            )
        );

        row.append(
            carrier,
            departure,
            journey,
            arrival
        );

        return row;
    };

    const renderFlightSlice = (
        slice,
        sliceIndex,
        sliceCount,
        offer
    ) => {
        const section = createFlightElement(
            'section',
            'flight-offer-slice'
        );

        const heading = createFlightElement(
            'div',
            'flight-slice-heading'
        );

        const headingLeft = createFlightElement(
            'div'
        );

        let legLabel = `Leg ${sliceIndex + 1}`;

        if (sliceCount === 1) {
            legLabel = 'Flight';
        } else if (sliceIndex === 0) {
            legLabel = 'Outbound';
        } else if (sliceIndex === 1) {
            legLabel = 'Return';
        }

        headingLeft.append(
            createFlightElement(
                'span',
                'flight-slice-label',
                legLabel
            ),
            createFlightElement(
                'strong',
                'flight-slice-route',
                `${flightPlaceCode(
                    slice?.origin
                )} → ${flightPlaceCode(
                    slice?.destination
                )}`
            )
        );

        const duration = formatFlightDuration(
            slice?.duration
        );

        if (duration) {
            heading.append(
                headingLeft,
                createFlightElement(
                    'span',
                    'flight-slice-duration',
                    duration
                )
            );
        } else {
            heading.append(headingLeft);
        }

        section.append(heading);

        const segments = Array.isArray(
            slice?.segments
        )
            ? slice.segments
            : [];

        segments.forEach((segment) => {
            section.append(
                renderFlightSegment(
                    segment,
                    offer
                )
            );
        });

        return section;
    };

    const renderFlightOffer = (offer) => {
        const card = createFlightElement(
            'article',
            'flight-offer-card'
        );

        const header = createFlightElement(
            'header',
            'flight-offer-header'
        );

        const identity = createFlightElement(
            'div',
            'flight-offer-identity'
        );

        const ownerName =
            offer?.owner?.name ||
            'Flight option';

        const ownerMark = createFlightElement(
            'span',
            'flight-owner-mark',
            ownerName
                .trim()
                .charAt(0)
                .toUpperCase() || 'F'
        );

        const ownerText = createFlightElement(
            'div'
        );

        ownerText.append(
            createFlightElement(
                'strong',
                'flight-owner-name',
                ownerName
            )
        );

        if (offer?.owner?.iata_code) {
            ownerText.append(
                createFlightElement(
                    'span',
                    'flight-owner-code',
                    offer.owner.iata_code
                )
            );
        }

        identity.append(
            ownerMark,
            ownerText
        );

        if (offer?.provider === 'fixture') {
            identity.append(
                createFlightElement(
                    'span',
                    'flight-demo-badge',
                    'DEMO DATA'
                )
            );
        }

        const price = createFlightElement(
            'div',
            'flight-offer-price'
        );

        price.append(
            createFlightElement(
                'span',
                '',
                'Total fare'
            ),
            createFlightElement(
                'strong',
                '',
                formatFlightMoney(
                    offer?.total_amount,
                    offer?.total_currency
                )
            )
        );

        header.append(
            identity,
            price
        );

        card.append(header);

        const slices = Array.isArray(
            offer?.slices
        )
            ? offer.slices
            : [];

        const sliceList = createFlightElement(
            'div',
            'flight-offer-slices'
        );

        slices.forEach((slice, index) => {
            sliceList.append(
                renderFlightSlice(
                    slice,
                    index,
                    slices.length,
                    offer
                )
            );
        });

        card.append(sliceList);

        const footer = createFlightElement(
            'footer',
            'flight-offer-footer'
        );

        if (offer?.requires_instant_payment) {
            footer.append(
                createFlightElement(
                    'span',
                    'flight-payment-badge',
                    'Instant payment required'
                )
            );
        }

        if (offer?.provider === 'fixture') {
            footer.append(
                createFlightElement(
                    'span',
                    'flight-demo-note',
                    'Development fixture — not live availability or a bookable fare.'
                )
            );
        }

        if (footer.childNodes.length > 0) {
            card.append(footer);
        }

        return card;
    };

    const renderOffers = (offers) => {
        resultsBox.textContent = '';
        resultsBox.classList.add(
            'flight-offer-list'
        );

        const heading = createFlightElement(
            'div',
            'flight-results-heading'
        );

        heading.append(
            createFlightElement(
                'strong',
                '',
                'Available flight options'
            ),
            createFlightElement(
                'span',
                '',
                `${offers.length} result${
                    offers.length === 1 ? '' : 's'
                }`
            )
        );

        resultsBox.append(heading);

        const cards = createFlightElement(
            'div',
            'flight-offer-cards'
        );

        offers.forEach((offer) => {
            cards.append(
                renderFlightOffer(offer)
            );
        });

        resultsBox.append(cards);
        resultsBox.hidden = false;
    };
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearErrors();
        hideStatus();

        resultsBox.hidden = true;
        resultsBox.textContent = '';

        submitButton.disabled = true;

        const originalButtonText = submitButton.textContent;
        submitButton.textContent = 'Searching...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const payload = await response.json().catch(() => ({}));

            if (response.status === 422) {
                showValidationErrors(payload.errors || {});
                return;
            }

            if (response.status === 503) {
                showStatus(
                    'Flight search is temporarily unavailable. ' +
                    'Our supplier connection is not ready yet. ' +
                    'Please try again later.',
                    'error'
                );

                return;
            }

            if (!response.ok) {
                showStatus(
                    'We could not complete your flight search. ' +
                    'Please try again.',
                    'error'
                );

                return;
            }

            const offers = Array.isArray(payload?.data?.offers)
                ? payload.data.offers
                : [];

            if (offers.length === 0) {
                showStatus(
                    'Search completed, but no flight options were found ' +
                    'for this itinerary.',
                    'info'
                );

                return;
            }

            showStatus(
                `${offers.length} flight option${
                    offers.length === 1 ? '' : 's'
                } found.`,
                'success'
            );

            renderOffers(offers);
        } catch (error) {
            showStatus(
                'The flight search service could not be reached. ' +
                'Please check your connection and try again.',
                'error'
            );
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    });
});
