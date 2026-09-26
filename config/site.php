<?php

/*
|--------------------------------------------------------------------------
| Public website content
|--------------------------------------------------------------------------
|
| Defaults for the customer-facing website. Admins override every value
| from Admin > Website (stored through SettingService), so nothing here
| needs editing to change an image, SEO text or tracking code.
|
*/

return [

    /*
    | Replaceable images: key => [label, default path under public/].
    | Admin > Website > Images uploads a new file for any key.
    */
    'images' => [
        'logo' => ['label' => 'Logo', 'default' => 'images/site/logo-mark.png'],
        'home.hero' => ['label' => 'Homepage hero', 'default' => 'images/site/hero.jpg'],
        'home.promo_flights' => ['label' => 'Homepage offer: flights', 'default' => 'images/site/promo-plane.jpg'],
        'home.promo_hotels' => ['label' => 'Homepage offer: hotels', 'default' => 'images/site/promo-hotel.jpg'],
        'home.promo_work_visa' => ['label' => 'Homepage offer: work visa', 'default' => 'images/site/promo-man.jpg'],
        'home.banner_visa' => ['label' => 'Homepage banner: visa services', 'default' => 'images/site/visa.jpg'],
        'home.banner_work_visa' => ['label' => 'Homepage banner: work visa', 'default' => 'images/site/workvisa.jpg'],
        'home.banner_holiday' => ['label' => 'Homepage banner: holiday packages', 'default' => 'images/site/holiday.jpg'],
        'home.app_phone' => ['label' => 'Homepage mobile app phone', 'default' => 'images/site/phone.png'],
        'home.newsletter' => ['label' => 'Homepage newsletter background', 'default' => 'images/site/cta.jpg'],
        'page.flights' => ['label' => 'Flights page banner', 'default' => 'images/site/promo-plane.jpg'],
        'page.hotels' => ['label' => 'Hotels page banner', 'default' => 'images/site/promo-hotel.jpg'],
        'page.tours' => ['label' => 'Tours page banner', 'default' => 'images/site/dest-bali.jpg'],
        'page.packages' => ['label' => 'Holiday packages page banner', 'default' => 'images/site/holiday.jpg'],
        'page.visa' => ['label' => 'Visa page banner', 'default' => 'images/site/visa.jpg'],
        'page.work_visa' => ['label' => 'Work visa page banner', 'default' => 'images/site/workvisa.jpg'],
        'page.contact' => ['label' => 'Contact page banner', 'default' => 'images/site/hero.jpg'],
        'page.about' => ['label' => 'About page banner', 'default' => 'images/site/dest-singapore.jpg'],
        'dest.dubai' => ['label' => 'Destination: Dubai', 'default' => 'images/site/dest-dubai.jpg'],
        'dest.bali' => ['label' => 'Destination: Bali', 'default' => 'images/site/dest-bali.jpg'],
        'dest.bangkok' => ['label' => 'Destination: Bangkok', 'default' => 'images/site/dest-bangkok.jpg'],
        'dest.singapore' => ['label' => 'Destination: Singapore', 'default' => 'images/site/dest-singapore.jpg'],
        'dest.maldives' => ['label' => 'Destination: Maldives', 'default' => 'images/site/dest-maldives.jpg'],
        'dest.london' => ['label' => 'Destination: London', 'default' => 'images/site/dest-london.jpg'],
        'dest.istanbul' => ['label' => 'Destination: Istanbul', 'default' => 'images/site/dest-istanbul.jpg'],
        'dest.paris' => ['label' => 'Destination: Paris', 'default' => 'images/site/dest-paris.jpg'],
    ],

    'image_upload_max_kb' => 4096,

    /*
    | Public pages with editable SEO: route name => [label, default title, default description].
    | Titles are rendered as-is (brand already included).
    */
    'seo_pages' => [
        'home' => [
            'label' => 'Homepage',
            'title' => 'Flights, Hotels, Tours & Visa from Dhaka | Eagle Global Hub',
            'description' => 'Book flights, hotels, tours and holiday packages, and get tourist visa and work visa processing with Eagle Global Hub, a travel agency in Dhaka, Bangladesh.',
        ],
        'flights.index' => [
            'label' => 'Flights',
            'title' => 'Cheap Flight Tickets from Dhaka | Eagle Global Hub',
            'description' => 'Search and compare domestic and international flights from Bangladesh. Check fares, baggage and schedules, then book securely with Eagle Global Hub.',
        ],
        'hotels.index' => [
            'label' => 'Hotels',
            'title' => 'Hotel Booking Worldwide | Eagle Global Hub',
            'description' => 'Find and compare hotels, resorts and apartments worldwide. Search by city and dates and book your stay with Eagle Global Hub.',
        ],
        'tours.index' => [
            'label' => 'Tours & Activities',
            'title' => 'Tours & Activities Abroad | Eagle Global Hub',
            'description' => 'Discover city tours, desert safaris, cruises and day trips in popular destinations, and book them with Eagle Global Hub from Bangladesh.',
        ],
        'packages.index' => [
            'label' => 'Holiday Packages',
            'title' => 'Holiday Packages from Bangladesh | Eagle Global Hub',
            'description' => 'Family, honeymoon and adventure holiday packages with flights, hotels and tours. Ask Eagle Global Hub for a customised itinerary.',
        ],
        'visa.index' => [
            'label' => 'Visa',
            'title' => 'Visa Requirements & Visa Assistance | Eagle Global Hub',
            'description' => 'Check visa requirements for your destination and get help with tourist, business, transit and student visa applications from Eagle Global Hub.',
        ],
        'work-visa.index' => [
            'label' => 'Work Visa',
            'title' => 'Work Visa Processing from Bangladesh | Eagle Global Hub',
            'description' => 'Work permit and skilled worker visa processing for popular countries. Document support and application tracking from Eagle Global Hub.',
        ],
        'contact' => [
            'label' => 'Contact',
            'title' => 'Contact Eagle Global Hub | Travel & Visa Support',
            'description' => 'Contact Eagle Global Hub for flight, hotel, tour, visa and work visa questions. Send us a message and our team will get back to you.',
        ],
        'about' => [
            'label' => 'About',
            'title' => 'About Eagle Global Hub | Travel & Visa Agency in Dhaka',
            'description' => 'Learn about Eagle Global Hub Ltd., a travel and visa agency in Dhaka, Bangladesh, helping people travel, work and study abroad.',
        ],
        'support' => [
            'label' => 'Support',
            'title' => 'Help & Support | Eagle Global Hub',
            'description' => 'Get help with bookings, payments, cancellations and visa applications at Eagle Global Hub.',
        ],
        'terms' => [
            'label' => 'Terms',
            'title' => 'Terms & Conditions | Eagle Global Hub',
            'description' => 'Terms and conditions for using the Eagle Global Hub website and booking services.',
        ],
    ],

    /*
    | Tracking & advertising integrations. Empty values are ignored.
    */
    'tracking_fields' => [
        'google_analytics_id' => ['label' => 'Google Analytics 4 Measurement ID', 'pattern' => '/^G-[A-Z0-9]{4,20}$/', 'placeholder' => 'G-XXXXXXXXXX'],
        'google_tag_manager_id' => ['label' => 'Google Tag Manager container ID', 'pattern' => '/^GTM-[A-Z0-9]{4,12}$/', 'placeholder' => 'GTM-XXXXXXX'],
        'google_ads_id' => ['label' => 'Google Ads conversion ID', 'pattern' => '/^AW-[0-9]{6,15}$/', 'placeholder' => 'AW-123456789'],
        'meta_pixel_id' => ['label' => 'Meta (Facebook) Pixel ID', 'pattern' => '/^[0-9]{8,20}$/', 'placeholder' => '123456789012345'],
        'tiktok_pixel_id' => ['label' => 'TikTok Pixel ID', 'pattern' => '/^[A-Z0-9]{10,30}$/', 'placeholder' => 'CXXXXXXXXXXXXXXX'],
        'google_site_verification' => ['label' => 'Google Search Console verification code', 'pattern' => '/^[A-Za-z0-9_\-]{10,100}$/', 'placeholder' => 'content value of the meta tag'],
        'facebook_domain_verification' => ['label' => 'Facebook domain verification code', 'pattern' => '/^[a-z0-9]{10,60}$/', 'placeholder' => 'content value of the meta tag'],
    ],

    'custom_code_max_length' => 20000,

    /*
    | Contact details shown on the website only when an admin fills them in.
    */
    'contact_fields' => [
        'phone' => 'Phone number',
        'whatsapp' => 'WhatsApp number',
        'email' => 'Support email',
        'address' => 'Office address',
        'hours' => 'Opening hours',
        'map_url' => 'Google Maps link',
        'facebook_url' => 'Facebook page URL',
        'instagram_url' => 'Instagram URL',
        'linkedin_url' => 'LinkedIn URL',
        'youtube_url' => 'YouTube URL',
    ],
];
