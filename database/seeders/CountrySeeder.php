<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = require database_path('data/countries.php');

        $now = now();
        $rows = [];

        foreach ($countries as [$name, $iso2, $iso3, $phoneCode]) {
            $rows[] = [
                'name' => $name,
                'iso2' => $iso2,
                'iso3' => $iso3,
                'phone_code' => $phoneCode,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Country::query()->upsert(
                $chunk,
                ['iso2'],
                ['name', 'iso3', 'phone_code', 'active', 'updated_at']
            );
        }
    }
}
