<?php

/*
|--------------------------------------------------------------------------
| Enquiries (quote requests)
|--------------------------------------------------------------------------
|
| One definition shared by the public request forms, their validation and
| the admin enquiry view. Each service lists the extra "detail" fields a
| customer fills in; they are stored in the enquiries.details JSON column.
|
| Field keys: label, type (text|date|time|month|number|select|textarea),
| required, min/max (numbers, or string length for text), options (select),
| after (another field this date must be on/after), future (date must be
| today or later), placeholder, autocomplete, inputmode, hint, wide.
|
*/

$travellerCount = fn (string $label, int $max, bool $required = true): array => [
    'label' => $label,
    'type' => 'number',
    'required' => $required,
    'min' => 1,
    'max' => $max,
    'inputmode' => 'numeric',
    'default' => 1,
];

return [

    'statuses' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'closed' => 'Closed',
    ],

    /*
    | Services that have a public request form at /request/{service}.
    */
    'services' => [
        'flights' => [
            'label' => 'Flights',
            'title' => 'Request a Flight Quote',
            'intro' => 'Tell us your route and dates. Our ticketing team checks fares with airlines and replies with options.',
            'icon' => 'plane',
            'image' => 'page.flights',
            'fields' => [
                'from' => ['label' => 'From (city or airport)', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Dhaka (DAC)', 'autocomplete' => 'off'],
                'to' => ['label' => 'To (city or airport)', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Dubai (DXB)', 'autocomplete' => 'off'],
                'departure_date' => ['label' => 'Departure date', 'type' => 'date', 'required' => true, 'future' => true],
                'return_date' => ['label' => 'Return date', 'type' => 'date', 'required' => false, 'after' => 'departure_date', 'hint' => 'Leave empty for one-way.'],
                'travellers' => $travellerCount('Travellers', 9),
                'cabin' => ['label' => 'Cabin class', 'type' => 'select', 'required' => false, 'options' => ['Economy', 'Premium economy', 'Business', 'First']],
            ],
        ],
        'hotels' => [
            'label' => 'Hotels',
            'title' => 'Request a Hotel Booking',
            'intro' => 'Share your city, dates and room needs. We check availability and rates with our hotel partners for you.',
            'icon' => 'hotel',
            'image' => 'page.hotels',
            'fields' => [
                'city' => ['label' => 'City, area or hotel', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Dubai, Downtown', 'autocomplete' => 'off', 'wide' => true],
                'check_in' => ['label' => 'Check-in', 'type' => 'date', 'required' => true, 'future' => true],
                'check_out' => ['label' => 'Check-out', 'type' => 'date', 'required' => true, 'after' => 'check_in', 'strict' => true],
                'rooms' => $travellerCount('Rooms', 9),
                'guests' => $travellerCount('Guests', 20),
            ],
        ],
        'tours' => [
            'label' => 'Tours & Activities',
            'title' => 'Request a Tour',
            'intro' => 'Tell us where you are going and what you would like to do. We suggest tours and confirm availability before you pay.',
            'icon' => 'palm',
            'image' => 'page.tours',
            'fields' => [
                'destination' => ['label' => 'Destination', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Bali, Indonesia', 'autocomplete' => 'off', 'wide' => true],
                'travel_date' => ['label' => 'Preferred date', 'type' => 'date', 'required' => false, 'future' => true],
                'guests' => $travellerCount('Guests', 30),
            ],
        ],
        'visa' => [
            'label' => 'Visa',
            'title' => 'Request Visa Assistance',
            'intro' => 'Tell us your passport and destination. A visa specialist reviews the requirements and explains the documents and fees.',
            'icon' => 'passport',
            'image' => 'page.visa',
            'fields' => [
                'nationality' => ['label' => 'Passport nationality', 'type' => 'text', 'required' => true, 'max' => 80, 'placeholder' => 'Bangladesh', 'autocomplete' => 'country-name'],
                'destination' => ['label' => 'Destination country', 'type' => 'text', 'required' => true, 'max' => 80, 'placeholder' => 'United Kingdom', 'autocomplete' => 'off'],
                'travel_date' => ['label' => 'Planned travel date', 'type' => 'date', 'required' => false, 'future' => true],
                'visa_type' => ['label' => 'Visa type', 'type' => 'select', 'required' => true, 'options' => ['Tourist', 'Business', 'Transit', 'Student', 'Medical', 'Family visit', 'Other']],
            ],
        ],
        'packages' => [
            'label' => 'Holiday Packages',
            'title' => 'Plan a Holiday Package',
            'intro' => 'Tell us where and when you want to go. We put together flights, hotels and tours into one package quote.',
            'icon' => 'gift',
            'image' => 'page.packages',
            'fields' => [
                'package_type' => ['label' => 'Package type', 'type' => 'select', 'required' => false, 'options' => ['Family', 'Honeymoon', 'Adventure', 'Customised']],
                'destination' => ['label' => 'Destination', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Maldives', 'autocomplete' => 'off'],
                'travel_month' => ['label' => 'Travel month', 'type' => 'month', 'required' => false, 'future' => true, 'placeholder' => 'YYYY-MM'],
                'nights' => ['label' => 'Nights', 'type' => 'number', 'required' => false, 'min' => 1, 'max' => 60, 'inputmode' => 'numeric'],
                'travellers' => $travellerCount('Travellers', 30),
                'budget' => ['label' => 'Budget (optional)', 'type' => 'text', 'required' => false, 'max' => 80, 'placeholder' => 'e.g. BDT 150,000 per person', 'autocomplete' => 'off'],
            ],
        ],
        'trains' => [
            'label' => 'Trains',
            'title' => 'Request Train Tickets',
            'intro' => 'Tell us your route and date. We check seats and fares and reply with the options available.',
            'icon' => 'train',
            'image' => 'page.contact',
            'fields' => [
                'from' => ['label' => 'From', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Departure station or city', 'autocomplete' => 'off'],
                'to' => ['label' => 'To', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Arrival station or city', 'autocomplete' => 'off'],
                'travel_date' => ['label' => 'Travel date', 'type' => 'date', 'required' => true, 'future' => true],
                'passengers' => $travellerCount('Passengers', 9),
            ],
        ],
        'buses' => [
            'label' => 'Buses',
            'title' => 'Request Bus Tickets',
            'intro' => 'Tell us your route and date. We check operators, seats and fares and get back to you.',
            'icon' => 'bus',
            'image' => 'page.contact',
            'fields' => [
                'from' => ['label' => 'From', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Departure city', 'autocomplete' => 'off'],
                'to' => ['label' => 'To', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Arrival city', 'autocomplete' => 'off'],
                'travel_date' => ['label' => 'Travel date', 'type' => 'date', 'required' => true, 'future' => true],
                'passengers' => $travellerCount('Passengers', 9),
            ],
        ],
        'cabs' => [
            'label' => 'Cabs',
            'title' => 'Request a Cab',
            'intro' => 'Airport transfer or city ride: tell us the pick-up, drop-off and time and we arrange a car for you.',
            'icon' => 'car',
            'image' => 'page.contact',
            'fields' => [
                'pickup' => ['label' => 'Pick-up location', 'type' => 'text', 'required' => true, 'max' => 160, 'placeholder' => 'Address, hotel or airport', 'autocomplete' => 'off', 'wide' => true],
                'dropoff' => ['label' => 'Drop-off location', 'type' => 'text', 'required' => true, 'max' => 160, 'placeholder' => 'Address, hotel or airport', 'autocomplete' => 'off', 'wide' => true],
                'pickup_date' => ['label' => 'Pick-up date', 'type' => 'date', 'required' => true, 'future' => true],
                'pickup_time' => ['label' => 'Pick-up time', 'type' => 'time', 'required' => false],
                'passengers' => $travellerCount('Passengers', 12),
            ],
        ],
        'insurance' => [
            'label' => 'Travel Insurance',
            'title' => 'Request Travel Insurance',
            'intro' => 'Tell us where you are going, for how long and who is travelling. We compare plans and send you a quote.',
            'icon' => 'shield',
            'image' => 'page.contact',
            'fields' => [
                'destination' => ['label' => 'Destination country', 'type' => 'text', 'required' => true, 'max' => 120, 'placeholder' => 'Schengen area', 'autocomplete' => 'off', 'wide' => true],
                'start_date' => ['label' => 'Cover start date', 'type' => 'date', 'required' => true, 'future' => true],
                'end_date' => ['label' => 'Cover end date', 'type' => 'date', 'required' => true, 'after' => 'start_date'],
                'travellers' => $travellerCount('Travellers', 10),
                'ages' => ['label' => 'Traveller ages', 'type' => 'text', 'required' => false, 'max' => 80, 'placeholder' => 'e.g. 42, 38, 9', 'inputmode' => 'numeric', 'autocomplete' => 'off', 'hint' => 'Separate ages with commas.'],
            ],
        ],
        'other' => [
            'label' => 'Other',
            'title' => 'Send a Travel Request',
            'intro' => 'Need something else? Describe what you need and the right person on our team will reply.',
            'icon' => 'headset',
            'image' => 'page.contact',
            'fields' => [
                'topic' => ['label' => 'What do you need help with?', 'type' => 'text', 'required' => true, 'max' => 150, 'placeholder' => 'e.g. Group travel, student visa', 'autocomplete' => 'off', 'wide' => true],
            ],
        ],
    ],

    /*
    | Labels for services that have no request form of their own.
    */
    'extra_service_labels' => [
        'contact' => 'Contact message',
    ],

    /*
    | Contact form detail fields (shown in the admin enquiry view).
    */
    'contact_fields' => [
        'subject' => ['label' => 'Subject'],
    ],

    /*
    | Holiday package types shown on the packages landing page.
    */
    'package_types' => [
        'family' => ['label' => 'Family', 'value' => 'Family', 'icon' => 'users', 'color' => 'c-blue', 'text' => 'Kid-friendly hotels, easy flight times and relaxed day plans for the whole family.'],
        'honeymoon' => ['label' => 'Honeymoon', 'value' => 'Honeymoon', 'icon' => 'heart', 'color' => 'c-red', 'text' => 'Romantic stays, private transfers and special touches planned around the two of you.'],
        'adventure' => ['label' => 'Adventure', 'value' => 'Adventure', 'icon' => 'map', 'color' => 'c-green', 'text' => 'Treks, water sports, safaris and active tours matched to your fitness and interests.'],
        'customised' => ['label' => 'Customised', 'value' => 'Customised', 'icon' => 'edit', 'color' => 'c-amber', 'text' => 'Your destination, your dates, your budget: we build the itinerary around what you ask for.'],
    ],
];
