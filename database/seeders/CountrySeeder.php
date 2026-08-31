<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Bangladesh',
                'iso2' => 'BD',
                'iso3' => 'BGD',
                'phone_code' => '+880',
            ],
            [
                'name' => 'India',
                'iso2' => 'IN',
                'iso3' => 'IND',
                'phone_code' => '+91',
            ],
            [
                'name' => 'Pakistan',
                'iso2' => 'PK',
                'iso3' => 'PAK',
                'phone_code' => '+92',
            ],
            [
                'name' => 'Nepal',
                'iso2' => 'NP',
                'iso3' => 'NPL',
                'phone_code' => '+977',
            ],
            [
                'name' => 'Bhutan',
                'iso2' => 'BT',
                'iso3' => 'BTN',
                'phone_code' => '+975',
            ],
            [
                'name' => 'Sri Lanka',
                'iso2' => 'LK',
                'iso3' => 'LKA',
                'phone_code' => '+94',
            ],
            [
                'name' => 'Maldives',
                'iso2' => 'MV',
                'iso3' => 'MDV',
                'phone_code' => '+960',
            ],
            [
                'name' => 'United Arab Emirates',
                'iso2' => 'AE',
                'iso3' => 'ARE',
                'phone_code' => '+971',
            ],
            [
                'name' => 'Saudi Arabia',
                'iso2' => 'SA',
                'iso3' => 'SAU',
                'phone_code' => '+966',
            ],
            [
                'name' => 'Qatar',
                'iso2' => 'QA',
                'iso3' => 'QAT',
                'phone_code' => '+974',
            ],
            [
                'name' => 'Oman',
                'iso2' => 'OM',
                'iso3' => 'OMN',
                'phone_code' => '+968',
            ],
            [
                'name' => 'Kuwait',
                'iso2' => 'KW',
                'iso3' => 'KWT',
                'phone_code' => '+965',
            ],
            [
                'name' => 'Bahrain',
                'iso2' => 'BH',
                'iso3' => 'BHR',
                'phone_code' => '+973',
            ],
            [
                'name' => 'Malaysia',
                'iso2' => 'MY',
                'iso3' => 'MYS',
                'phone_code' => '+60',
            ],
            [
                'name' => 'Singapore',
                'iso2' => 'SG',
                'iso3' => 'SGP',
                'phone_code' => '+65',
            ],
            [
                'name' => 'Thailand',
                'iso2' => 'TH',
                'iso3' => 'THA',
                'phone_code' => '+66',
            ],
            [
                'name' => 'Indonesia',
                'iso2' => 'ID',
                'iso3' => 'IDN',
                'phone_code' => '+62',
            ],
            [
                'name' => 'China',
                'iso2' => 'CN',
                'iso3' => 'CHN',
                'phone_code' => '+86',
            ],
            [
                'name' => 'Japan',
                'iso2' => 'JP',
                'iso3' => 'JPN',
                'phone_code' => '+81',
            ],
            [
                'name' => 'South Korea',
                'iso2' => 'KR',
                'iso3' => 'KOR',
                'phone_code' => '+82',
            ],
            [
                'name' => 'Turkey',
                'iso2' => 'TR',
                'iso3' => 'TUR',
                'phone_code' => '+90',
            ],
            [
                'name' => 'United Kingdom',
                'iso2' => 'GB',
                'iso3' => 'GBR',
                'phone_code' => '+44',
            ],
            [
                'name' => 'United States',
                'iso2' => 'US',
                'iso3' => 'USA',
                'phone_code' => '+1',
            ],
            [
                'name' => 'Canada',
                'iso2' => 'CA',
                'iso3' => 'CAN',
                'phone_code' => '+1',
            ],
            [
                'name' => 'Australia',
                'iso2' => 'AU',
                'iso3' => 'AUS',
                'phone_code' => '+61',
            ],
            [
                'name' => 'New Zealand',
                'iso2' => 'NZ',
                'iso3' => 'NZL',
                'phone_code' => '+64',
            ],
            [
                'name' => 'France',
                'iso2' => 'FR',
                'iso3' => 'FRA',
                'phone_code' => '+33',
            ],
            [
                'name' => 'Germany',
                'iso2' => 'DE',
                'iso3' => 'DEU',
                'phone_code' => '+49',
            ],
            [
                'name' => 'Italy',
                'iso2' => 'IT',
                'iso3' => 'ITA',
                'phone_code' => '+39',
            ],
            [
                'name' => 'Spain',
                'iso2' => 'ES',
                'iso3' => 'ESP',
                'phone_code' => '+34',
            ],
        ];

        foreach ($countries as $country) {
            Country::query()->updateOrCreate(
                [
                    'iso2' => $country['iso2'],
                ],
                [
                    'name' => $country['name'],
                    'iso3' => $country['iso3'],
                    'phone_code' => $country['phone_code'],
                    'is_active' => true,
                ]
            );
        }
    }
}