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

            resultsBox.hidden = false;
            resultsBox.textContent =
                'Flight offers were returned successfully. ' +
                'Detailed supplier result cards will be enabled with ' +
                'the supplier integration layer.';
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
