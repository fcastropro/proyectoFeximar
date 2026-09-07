<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Province;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $ecuador = Country::query()->where('iso2', 'EC')->first();

        if (! $ecuador) {
            throw new RuntimeException('Ecuador (EC) must exist before seeding provinces. Run CountrySeeder first.');
        }

        $provinces = [
            ['name' => 'Azuay', 'code' => 'AZ'],
            ['name' => 'Bolívar', 'code' => 'BO'],
            ['name' => 'Cañar', 'code' => 'CN'],
            ['name' => 'Carchi', 'code' => 'CR'],
            ['name' => 'Chimborazo', 'code' => 'CH'],
            ['name' => 'Cotopaxi', 'code' => 'CT'],
            ['name' => 'El Oro', 'code' => 'EO'],
            ['name' => 'Esmeraldas', 'code' => 'ES'],
            ['name' => 'Galápagos', 'code' => 'GA'],
            ['name' => 'Guayas', 'code' => 'GU'],
            ['name' => 'Imbabura', 'code' => 'IM'],
            ['name' => 'Loja', 'code' => 'LO'],
            ['name' => 'Los Ríos', 'code' => 'LR'],
            ['name' => 'Manabí', 'code' => 'MN'],
            ['name' => 'Morona Santiago', 'code' => 'MS'],
            ['name' => 'Napo', 'code' => 'NA'],
            ['name' => 'Orellana', 'code' => 'OR'],
            ['name' => 'Pastaza', 'code' => 'PA'],
            ['name' => 'Pichincha', 'code' => 'PI'],
            ['name' => 'Santa Elena', 'code' => 'SE'],
            ['name' => 'Santo Domingo de los Tsáchilas', 'code' => 'SD'],
            ['name' => 'Sucumbíos', 'code' => 'SU'],
            ['name' => 'Tungurahua', 'code' => 'TU'],
            ['name' => 'Zamora Chinchipe', 'code' => 'ZC'],
        ];

        foreach ($provinces as $province) {
            Province::query()->updateOrCreate(
                [
                    'country_id' => $ecuador->id,
                    'name' => $province['name'],
                ],
                [
                    'code' => $province['code'],
                    'active' => true,
                ]
            );
        }
    }
}
