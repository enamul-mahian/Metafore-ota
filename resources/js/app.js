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

    const createTravelerTextField = (
        labelText,
        inputType = 'text'
    ) => {
        const field = createFlightElement(
            'label',
            'flight-traveler-field'
        );

        field.append(
            createFlightElement(
                'span',
                'flight-traveler-field-label',
                labelText
            )
        );

        const input = createFlightElement(
            'input',
            'flight-traveler-input'
        );

        input.type = inputType;
        input.autocomplete = 'off';

        field.append(input);

        return field;
    };

    const createTravelerTitleField = () => {
        const field = createFlightElement(
            'label',
            'flight-traveler-field'
        );

        field.append(
            createFlightElement(
                'span',
                'flight-traveler-field-label',
                'Title'
            )
        );

        const select = createFlightElement(
            'select',
            'flight-traveler-input'
        );

        [
            ['', 'Select'],
            ['mr', 'Mr'],
            ['ms', 'Ms'],
            ['mrs', 'Mrs'],
            ['mstr', 'Master'],
            ['miss', 'Miss'],
        ].forEach(([value, label]) => {
            const option = createFlightElement(
                'option',
                '',
                label
            );

            option.value = value;

            select.append(option);
        });

        field.append(select);

        return field;
    };

    const clearTravelerValidationFeedback = (review) => {
        review
            .querySelector(
                '[data-flight-traveler-validation-feedback]'
            )
            ?.remove();

        review
            .querySelectorAll(
                '.flight-traveler-input.is-invalid'
            )
            .forEach((control) => {
                control.classList.remove(
                    'is-invalid'
                );
            });
    };

    const showTravelerValidationFeedback = (
        review,
        message,
        tone = 'error'
    ) => {
        review
            .querySelector(
                '[data-flight-traveler-validation-feedback]'
            )
            ?.remove();

        const feedback = createFlightElement(
            'div',
            (
                'flight-traveler-validation-feedback '
                + (
                    tone === 'success'
                        ? 'is-success'
                        : 'is-error'
                )
            ),
            message
        );

        feedback.dataset
            .flightTravelerValidationFeedback = '';

        const actions = review.querySelector(
            '.flight-traveler-actions'
        );

        if (actions) {
            actions.before(feedback);
        } else {
            review.append(feedback);
        }
    };

    const collectTravelerPayload = (review) => {
        return Array.from(
            review.querySelectorAll(
                '.flight-traveler-card'
            )
        ).map((traveler) => {
            const value = (fieldName) => {
                const control =
                    traveler.querySelector(
                        (
                            '[data-flight-traveler-field="'
                            + fieldName
                            + '"]'
                        )
                    );

                return String(
                    control?.value || ''
                ).trim();
            };

            return {
                type:
                    traveler.dataset
                        .flightTravelerType || '',
                title: value('title'),
                given_name:
                    value('given_name'),
                family_name:
                    value('family_name'),
                date_of_birth:
                    value('date_of_birth'),
            };
        });
    };

    const markTravelerValidationErrors = (
        review,
        errors
    ) => {
        Object.keys(errors || {})
            .forEach((key) => {
                const match = key.match(
                    /^travelers\.(\d+)\.(title|given_name|family_name|date_of_birth)$/
                );

                if (!match) {
                    return;
                }

                const travelerIndex =
                    Number(match[1]);

                const fieldName = match[2];

                const traveler = review
                    .querySelectorAll(
                        '.flight-traveler-card'
                    )[travelerIndex];

                traveler
                    ?.querySelector(
                        (
                            '[data-flight-traveler-field="'
                            + fieldName
                            + '"]'
                        )
                    )
                    ?.classList
                    .add('is-invalid');
            });
    };

    const firstTravelerValidationMessage = (
        payload
    ) => {
        const messages = Object.values(
            payload?.errors || {}
        ).flat();

        return messages.find(
            (message) =>
                typeof message === 'string'
        ) || payload?.message || (
            'Please review the traveler details '
            + 'and try again.'
        );
    };



    async function createFlightBookingDraft(
        selectionToken,
        travelers,
    ) {
        const resultsElement =
            document.querySelector(
                '[data-flight-results]'
            );

        const flightBookingDraftUrl =
            resultsElement
                ?.dataset
                .flightBookingDraftUrl;

        if (!flightBookingDraftUrl) {
            throw new Error(
                'Secure booking draft endpoint is unavailable.'
            );
        }

        const csrfToken =
            document.querySelector(
                '[data-flight-search-form] input[name="_token"]'
            )?.value;

        if (!csrfToken) {
            throw new Error(
                'Security token is unavailable. Please refresh and try again.'
            );
        }

        const response =
            await fetch(
                flightBookingDraftUrl,
                {
                    method:
                        'POST',

                    credentials:
                        'same-origin',

                    headers: {
                        Accept:
                            'application/json',

                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken,
                    },

                    body:
                        JSON.stringify({
                            selection_token:
                                selectionToken,

                            travelers,
                        }),
                },
            );

        let payload = {};

        try {
            payload =
                await response.json();
        } catch {
            payload = {};
        }

        if (!response.ok) {
            throw new Error(
                payload?.message
                || 'Unable to create the secure booking draft. Please try again.'
            );
        }

        const bookingDraftToken =
            payload
                ?.data
                ?.booking_draft_token;

        const travelerCount =
            payload
                ?.data
                ?.traveler_count;

        const expiresInSeconds =
            payload
                ?.data
                ?.expires_in_seconds;

        if (
            typeof bookingDraftToken
                !== 'string'
            || bookingDraftToken.length
                !== 64
        ) {
            throw new Error(
                'The secure booking draft response was invalid.'
            );
        }

        if (resultsElement) {
            resultsElement
                .dataset
                .bookingDraftToken =
                    bookingDraftToken;

            if (
                Number.isInteger(
                    expiresInSeconds
                )
            ) {
                resultsElement
                    .dataset
                    .bookingDraftExpiresInSeconds =
                        String(
                            expiresInSeconds
                        );
            }

            const oldNotice =
                resultsElement
                    .querySelector(
                        '[data-flight-booking-draft-status]'
                    );

            oldNotice?.remove();

            const notice =
                document.createElement(
                    'div'
                );

            notice.className =
                'flight-booking-draft-status';

            notice
                .dataset
                .flightBookingDraftStatus =
                    '';

            notice.setAttribute(
                'role',
                'status'
            );

            notice.setAttribute(
                'aria-live',
                'polite'
            );

            const countText =
                Number.isInteger(
                    travelerCount
                )
                    ? ' for '
                        + travelerCount
                        + ' traveler'
                        + (
                            travelerCount === 1
                                ? ''
                                : 's'
                        )
                    : '';

            notice.textContent =
                'Secure booking draft created'
                + countText
                + '. This is not a supplier booking, ticket, payment, or confirmed reservation. The draft is short-lived and expires automatically.';

            resultsElement.append(
                notice
            );
        }

        return payload.data;
    }

const validateFlightTravelers = async (
        review,
        selectionToken,
        button
    ) => {
        const validationUrl =
            resultsBox.dataset
                .flightTravelerValidationUrl;

        const csrfToken = form
            .querySelector(
                'input[name="_token"]'
            )
            ?.value;

        if (
            !validationUrl ||
            !selectionToken ||
            !csrfToken
        ) {
            showStatus(
                (
                    'Traveler validation is not '
                    + 'available right now. '
                    + 'Please search again.'
                ),
                'error'
            );

            return;
        }

        clearTravelerValidationFeedback(
            review
        );

        const travelers =
            collectTravelerPayload(review);

        button.disabled = true;
        button.textContent =
            'Validating travelers...';

        let validationPassed = false;

        try {
            const response = await fetch(
                validationUrl,
                {
                    method: 'POST',
                    headers: {
                        Accept:
                            'application/json',
                        'Content-Type':
                            'application/json',
                        'X-CSRF-TOKEN':
                            csrfToken,
                    },
                    body: JSON.stringify({
                        selection_token:
                            selectionToken,
                        travelers,
                    }),
                }
            );

            const payload = await response
                .json()
                .catch(() => ({}));

            if (response.status === 422) {
                markTravelerValidationErrors(
                    review,
                    payload?.errors
                );

                showTravelerValidationFeedback(
                    review,
                    firstTravelerValidationMessage(
                        payload
                    ),
                    'error'
                );

                showStatus(
                    (
                        'Please correct the traveler '
                        + 'details highlighted below.'
                    ),
                    'error'
                );

                return;
            }

            if (response.status === 410) {
                showTravelerValidationFeedback(
                    review,
                    (
                        payload?.message ||
                        (
                            'This flight offer has '
                            + 'expired. Please search '
                            + 'again.'
                        )
                    ),
                    'error'
                );

                showStatus(
                    (
                        'This selected flight is no '
                        + 'longer available. '
                        + 'Please search again.'
                    ),
                    'error'
                );

                return;
            }

            if (!response.ok) {
                showTravelerValidationFeedback(
                    review,
                    (
                        'Traveler details could not '
                        + 'be validated right now. '
                        + 'Please try again.'
                    ),
                    'error'
                );

                return;
            }
            await createFlightBookingDraft(
                selectionToken,
                travelers,
            );


            validationPassed = true;

            review.dataset
                .flightTravelersValidated =
                'true';

            showTravelerValidationFeedback(
                review,
                (
                    'Traveler details passed '
                    + 'server-side validation. '
                    + 'Nothing has been booked yet.'
                ),
                'success'
            );

            showStatus(
                (
                    'Traveler details validated '
                    + 'successfully.'
                ),
                'success'
            );

            button.textContent =
                'Travelers validated';

            button.disabled = true;
        } catch (error) {
            showTravelerValidationFeedback(
                review,
                (
                    'Traveler validation could not '
                    + 'be completed. Please check '
                    + 'your connection and try again.'
                ),
                'error'
            );
        } finally {
            if (!validationPassed) {
                button.disabled = false;
                button.textContent =
                    'Validate travelers';
            }
        }
    };
    const renderTravelerReview = (selection, selectionToken) => {
        const previous = resultsBox.querySelector(
            '[data-flight-traveler-review]'
        );

        if (previous) {
            previous.remove();
        }

        const criteria = selection?.criteria || {};
        const offer = selection?.offer || {};

        const review = createFlightElement(
            'section',
            'flight-traveler-review'
        );

        review.dataset.flightTravelerReview = '';

        const reviewHeader = createFlightElement(
            'div',
            'flight-traveler-review-header'
        );

        const reviewHeading = createFlightElement(
            'div'
        );

        reviewHeading.append(
            createFlightElement(
                'span',
                'flight-review-kicker',
                'SECURE SELECTION'
            ),
            createFlightElement(
                'h3',
                '',
                'Traveler details'
            ),
            createFlightElement(
                'p',
                '',
                (
                    'Your selected fare was resolved from the '
                    + 'server-stored offer. Enter traveler details '
                    + 'for review.'
                )
            )
        );

        const selectedFare = createFlightElement(
            'div',
            'flight-selected-fare'
        );

        selectedFare.append(
            createFlightElement(
                'span',
                '',
                offer?.owner?.name || 'Selected flight'
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

        reviewHeader.append(
            reviewHeading,
            selectedFare
        );

        review.append(reviewHeader);

        if (offer?.provider === 'fixture') {
            review.append(
                createFlightElement(
                    'div',
                    'flight-review-demo-warning',
                    (
                        'DEMO DATA — this selection is for development '
                        + 'testing only and is not a live booking.'
                    )
                )
            );
        }

        const passengerSummary = createFlightElement(
            'div',
            'flight-passenger-summary'
        );

        const passengerGroups = [
            [
                'Adult',
                Math.max(
                    0,
                    Number(criteria?.adults || 0)
                ),
            ],
            [
                'Child',
                Math.max(
                    0,
                    Number(criteria?.children || 0)
                ),
            ],
            [
                'Infant',
                Math.max(
                    0,
                    Number(criteria?.infants || 0)
                ),
            ],
        ];

        passengerGroups.forEach(([label, count]) => {
            if (count <= 0) {
                return;
            }

            passengerSummary.append(
                createFlightElement(
                    'span',
                    'flight-passenger-pill',
                    `${count} ${label}${
                        count === 1 ? '' : 's'
                    }`
                )
            );
        });

        review.append(passengerSummary);

        const travelerList = createFlightElement(
            'div',
            'flight-traveler-list'
        );

        let travelerNumber = 0;

        passengerGroups.forEach(
            ([travelerType, count]) => {
                for (
                    let index = 0;
                    index < count;
                    index += 1
                ) {
                    travelerNumber += 1;

                    const traveler = createFlightElement(
                        'article',
                        'flight-traveler-card'
                    );

                    traveler.dataset
                        .flightTravelerType =
                        travelerType.toLowerCase();

                    const travelerHeader =
                        createFlightElement(
                            'div',
                            'flight-traveler-card-header'
                        );

                    travelerHeader.append(
                        createFlightElement(
                            'strong',
                            '',
                            `Traveler ${travelerNumber}`
                        ),
                        createFlightElement(
                            'span',
                            '',
                            travelerType
                        )
                    );

                    const fields = createFlightElement(
                        'div',
                        'flight-traveler-fields'
                    );

                    fields.append(
                        createTravelerTitleField(),
                        createTravelerTextField(
                            'Given name'
                        ),
                        createTravelerTextField(
                            'Family name'
                        ),
                        createTravelerTextField(
                            'Date of birth',
                            'date'
                        )
                    );

                    const travelerControls =
                        fields.querySelectorAll(
                            '.flight-traveler-input'
                        );

                    const travelerFieldNames = [
                        'title',
                        'given_name',
                        'family_name',
                        'date_of_birth',
                    ];

                    travelerControls.forEach(
                        (control, fieldIndex) => {
                            control.dataset
                                .flightTravelerField =
                                travelerFieldNames[
                                    fieldIndex
                                ];

                            control.required = true;

                            const clearFieldError =
                                () => {
                                    control.classList
                                        .remove(
                                            'is-invalid'
                                        );

                                    review.dataset
                                        .flightTravelersValidated =
                                        'false';
                                };

                            control.addEventListener(
                                'input',
                                clearFieldError
                            );

                            control.addEventListener(
                                'change',
                                clearFieldError
                            );
                        }
                    );

                    traveler.append(
                        travelerHeader,
                        fields
                    );

                    travelerList.append(traveler);
                }
            }
        );

        review.append(travelerList);

        const draftNotice = createFlightElement(
            'div',
            'flight-traveler-draft-notice'
        );

        draftNotice.append(
            createFlightElement(
                'strong',
                '',
                'Draft only'
            ),
            createFlightElement(
                'span',
                '',
                (
                    'Traveler details are stored only in a short-lived encrypted server-side booking draft after successful validation. No supplier booking, ticket, payment, or confirmed reservation is created.. '
                    + 'They are sent only when you choose '
                    + 'Validate travelers.'
                )
            )
        );

        review.append(draftNotice);

        const reviewActions = createFlightElement(
            'div',
            'flight-traveler-actions'
        );

        const continueButton = createFlightElement(
            'button',
            'flight-booking-continue',
            'Validate travelers'
        );

        continueButton.type = 'button';
        continueButton.disabled = false;
        continueButton.title =
            'Validate traveler details securely.';

        continueButton.addEventListener(
            'click',
            () => {
                validateFlightTravelers(
                    review,
                    selectionToken,
                    continueButton
                );
            }
        );

        reviewActions.append(
            createFlightElement(
                'span',
                '',
                (
                    'Traveler details are validated '
                    + 'server-side. This step does '
                    + 'not create a booking.'
                )
            ),
            continueButton
        );

        review.append(reviewActions);

        resultsBox.append(review);

        review.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    };

    const resolveFlightSelection = async (
        offer,
        button
    ) => {
        const selectionToken =
            offer?.selection_token;

        const selectionUrl =
            resultsBox.dataset.flightSelectUrl;

        const csrfToken = form
            .querySelector(
                'input[name="_token"]'
            )
            ?.value;

        if (
            !selectionToken ||
            !selectionUrl ||
            !csrfToken
        ) {
            showStatus(
                (
                    'This flight cannot be selected right now. '
                    + 'Please search again.'
                ),
                'error'
            );

            return;
        }

        button.disabled = true;
        button.textContent = 'Selecting...';

        try {
            const response = await fetch(
                selectionUrl,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type':
                            'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        selection_token:
                            selectionToken,
                    }),
                }
            );

            const payload = await response
                .json()
                .catch(() => ({}));

            if (!response.ok) {
                if (response.status === 410) {
                    showStatus(
                        payload?.message ||
                            (
                                'This flight offer has expired. '
                                + 'Please search again.'
                            ),
                        'error'
                    );

                    return;
                }

                showStatus(
                    (
                        'Could not select this flight right now. '
                        + 'Please try again.'
                    ),
                    'error'
                );

                return;
            }

            renderTravelerReview(
                payload?.data,
                selectionToken
            );

            showStatus(
                (
                    'Flight selected securely. '
                    + 'Add traveler details below.'
                ),
                'success'
            );
        } catch (error) {
            showStatus(
                (
                    'Could not select this flight right now. '
                    + 'Please check your connection and try again.'
                ),
                'error'
            );
        } finally {
            button.disabled =
                !selectionToken;

            button.textContent =
                selectionToken
                    ? 'Select Flight'
                    : 'Selection unavailable';
        }
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

        const selectButton = createFlightElement(
            'button',
            'flight-select-button',
            offer?.selection_token
                ? 'Select Flight'
                : 'Selection unavailable'
        );

        selectButton.type = 'button';
        selectButton.disabled =
            !offer?.selection_token;

        if (offer?.selection_token) {
            selectButton.addEventListener(
                'click',
                () => {
                    resolveFlightSelection(
                        offer,
                        selectButton
                    );
                }
            );
        }

        footer.append(selectButton);
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
