<?php

$visibleByDefault = [
    'enabled' => true,
    'public_visible' => true,
    'authenticated_visible' => true,
    'admin_visible' => true,
    'message' => null,
];

return [
    'flights' => [
        'label' => 'Flights',
        'description' => 'Flight search and booking preparation interfaces.',
        'default' => $visibleByDefault,
    ],
    'hotels' => [
        'label' => 'Hotels',
        'description' => 'Hotel discovery interfaces, independent of provider activation.',
        'default' => $visibleByDefault,
    ],
    'tours' => [
        'label' => 'Tours',
        'description' => 'Tour discovery interfaces, independent of provider activation.',
        'default' => $visibleByDefault,
    ],
    'visa' => [
        'label' => 'Visa',
        'description' => 'Visa information interfaces, independent of provider activation.',
        'default' => $visibleByDefault,
    ],
    'bookings' => [
        'label' => 'Bookings',
        'description' => 'Customer booking preparation, history, and status interfaces.',
        'default' => $visibleByDefault,
    ],
    'payments' => [
        'label' => 'Payments',
        'description' => 'Payment readiness, submission, status, and reconciliation interfaces.',
        'default' => [
            ...$visibleByDefault,
            'public_visible' => false,
        ],
    ],
    'support' => [
        'label' => 'Support',
        'description' => 'Public and authenticated support information.',
        'default' => $visibleByDefault,
    ],
    'about' => [
        'label' => 'About',
        'description' => 'Public and authenticated company information.',
        'default' => $visibleByDefault,
    ],
    'account' => [
        'label' => 'Account',
        'description' => 'Authenticated customer account overview.',
        'default' => $visibleByDefault,
    ],
    'dashboard' => [
        'label' => 'Dashboard',
        'description' => 'Authenticated customer and administrative dashboard.',
        'default' => $visibleByDefault,
    ],
];
