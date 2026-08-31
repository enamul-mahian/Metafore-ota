<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use RuntimeException;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            ['country' => 'BD', 'name' => 'Dhaka', 'code' => 'DAC', 'timezone' => 'Asia/Dhaka'],
            ['country' => 'BD', 'name' => 'Chattogram', 'code' => 'CGP', 'timezone' => 'Asia/Dhaka'],
            ['country' => 'BD', 'name' => 'Sylhet', 'code' => 'ZYL', 'timezone' => 'Asia/Dhaka'],

            ['country' => 'IN', 'name' => 'Delhi', 'code' => 'DEL', 'timezone' => 'Asia/Kolkata'],
            ['country' => 'IN', 'name' => 'Mumbai', 'code' => 'BOM', 'timezone' => 'Asia/Kolkata'],
            ['country' => 'IN', 'name' => 'Kolkata', 'code' => 'CCU', 'timezone' => 'Asia/Kolkata'],
            ['country' => 'IN', 'name' => 'Chennai', 'code' => 'MAA', 'timezone' => 'Asia/Kolkata'],
            ['country' => 'IN', 'name' => 'Bengaluru', 'code' => 'BLR', 'timezone' => 'Asia/Kolkata'],

            ['country' => 'PK', 'name' => 'Islamabad', 'code' => 'ISB', 'timezone' => 'Asia/Karachi'],
            ['country' => 'PK', 'name' => 'Karachi', 'code' => 'KHI', 'timezone' => 'Asia/Karachi'],
            ['country' => 'PK', 'name' => 'Lahore', 'code' => 'LHE', 'timezone' => 'Asia/Karachi'],

            ['country' => 'NP', 'name' => 'Kathmandu', 'code' => 'KTM', 'timezone' => 'Asia/Kathmandu'],
            ['country' => 'BT', 'name' => 'Paro', 'code' => 'PBH', 'timezone' => 'Asia/Thimphu'],
            ['country' => 'LK', 'name' => 'Colombo', 'code' => 'CMB', 'timezone' => 'Asia/Colombo'],
            ['country' => 'MV', 'name' => 'Male', 'code' => 'MLE', 'timezone' => 'Indian/Maldives'],

            ['country' => 'AE', 'name' => 'Dubai', 'code' => 'DXB', 'timezone' => 'Asia/Dubai'],
            ['country' => 'AE', 'name' => 'Abu Dhabi', 'code' => 'AUH', 'timezone' => 'Asia/Dubai'],
            ['country' => 'AE', 'name' => 'Sharjah', 'code' => 'SHJ', 'timezone' => 'Asia/Dubai'],

            ['country' => 'SA', 'name' => 'Riyadh', 'code' => 'RUH', 'timezone' => 'Asia/Riyadh'],
            ['country' => 'SA', 'name' => 'Jeddah', 'code' => 'JED', 'timezone' => 'Asia/Riyadh'],
            ['country' => 'SA', 'name' => 'Dammam', 'code' => 'DMM', 'timezone' => 'Asia/Riyadh'],
            ['country' => 'SA', 'name' => 'Medina', 'code' => 'MED', 'timezone' => 'Asia/Riyadh'],

            ['country' => 'QA', 'name' => 'Doha', 'code' => 'DOH', 'timezone' => 'Asia/Qatar'],
            ['country' => 'OM', 'name' => 'Muscat', 'code' => 'MCT', 'timezone' => 'Asia/Muscat'],
            ['country' => 'KW', 'name' => 'Kuwait City', 'code' => 'KWI', 'timezone' => 'Asia/Kuwait'],
            ['country' => 'BH', 'name' => 'Manama', 'code' => 'BAH', 'timezone' => 'Asia/Bahrain'],

            ['country' => 'MY', 'name' => 'Kuala Lumpur', 'code' => 'KUL', 'timezone' => 'Asia/Kuala_Lumpur'],
            ['country' => 'SG', 'name' => 'Singapore', 'code' => 'SIN', 'timezone' => 'Asia/Singapore'],
            ['country' => 'TH', 'name' => 'Bangkok', 'code' => 'BKK', 'timezone' => 'Asia/Bangkok'],
            ['country' => 'ID', 'name' => 'Jakarta', 'code' => 'JKT', 'timezone' => 'Asia/Jakarta'],

            ['country' => 'CN', 'name' => 'Beijing', 'code' => 'BJS', 'timezone' => 'Asia/Shanghai'],
            ['country' => 'CN', 'name' => 'Shanghai', 'code' => 'SHA', 'timezone' => 'Asia/Shanghai'],
            ['country' => 'JP', 'name' => 'Tokyo', 'code' => 'TYO', 'timezone' => 'Asia/Tokyo'],
            ['country' => 'KR', 'name' => 'Seoul', 'code' => 'SEL', 'timezone' => 'Asia/Seoul'],

            ['country' => 'TR', 'name' => 'Istanbul', 'code' => 'IST', 'timezone' => 'Europe/Istanbul'],
            ['country' => 'GB', 'name' => 'London', 'code' => 'LON', 'timezone' => 'Europe/London'],
            ['country' => 'US', 'name' => 'New York', 'code' => 'NYC', 'timezone' => 'America/New_York'],
            ['country' => 'CA', 'name' => 'Toronto', 'code' => 'YTO', 'timezone' => 'America/Toronto'],
            ['country' => 'AU', 'name' => 'Sydney', 'code' => 'SYD', 'timezone' => 'Australia/Sydney'],
            ['country' => 'NZ', 'name' => 'Auckland', 'code' => 'AKL', 'timezone' => 'Pacific/Auckland'],

            ['country' => 'FR', 'name' => 'Paris', 'code' => 'PAR', 'timezone' => 'Europe/Paris'],
            ['country' => 'DE', 'name' => 'Frankfurt', 'code' => 'FRA', 'timezone' => 'Europe/Berlin'],
            ['country' => 'IT', 'name' => 'Rome', 'code' => 'ROM', 'timezone' => 'Europe/Rome'],
            ['country' => 'ES', 'name' => 'Madrid', 'code' => 'MAD', 'timezone' => 'Europe/Madrid'],
        ];

        foreach ($cities as $city) {
            $countryId = Country::query()
                ->where('iso2', $city['country'])
                ->value('id');

            if ($countryId === null) {
                throw new RuntimeException(
                    "Country [{$city['country']}] must be seeded before CitySeeder."
                );
            }

            City::query()->updateOrCreate(
                [
                    'country_id' => $countryId,
                    'name' => $city['name'],
                ],
                [
                    'code' => $city['code'],
                    'timezone' => $city['timezone'],
                    'is_active' => true,
                ]
            );
        }
    }
}