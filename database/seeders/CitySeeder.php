<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;
use RuntimeException;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $ecuador = Country::query()->where('iso2', 'EC')->first();

        if (! $ecuador) {
            throw new RuntimeException('Ecuador (EC) must exist before seeding cities. Run CountrySeeder first.');
        }

        $citiesByProvince = [
            'Azuay' => ['Cuenca', 'Gualaceo', 'Paute', 'Sigsig', 'Santa Isabel'],
            'Bolívar' => ['Guaranda', 'San Miguel', 'Chillanes'],
            'Cañar' => ['Azogues', 'Biblián', 'Cañar', 'La Troncal'],
            'Carchi' => ['Tulcán', 'Montúfar', 'Espejo', 'Mira'],
            'Chimborazo' => ['Riobamba', 'Guano', 'Colta', 'Alausí', 'Chambo'],
            'Cotopaxi' => ['Latacunga', 'Salcedo', 'Pujilí', 'Saquisilí', 'Sigchos'],
            'El Oro' => ['Machala', 'Pasaje', 'Santa Rosa', 'Huaquillas', 'Arenillas'],
            'Esmeraldas' => ['Esmeraldas', 'Atacames', 'Quinindé', 'Muisne'],
            'Galápagos' => ['San Cristóbal', 'Santa Cruz', 'Isabela'],
            'Guayas' => ['Guayaquil', 'Durán', 'Samborondón', 'Daule', 'Milagro', 'Playas'],
            'Imbabura' => ['Ibarra', 'Otavalo', 'Cotacachi', 'Antonio Ante', 'Atuntaqui'],
            'Loja' => ['Loja', 'Catamayo', 'Cariamanga', 'Macará'],
            'Los Ríos' => ['Babahoyo', 'Quevedo', 'Ventanas', 'Vinces'],
            'Manabí' => ['Portoviejo', 'Manta', 'Chone', 'Jipijapa', 'Montecristi'],
            'Morona Santiago' => ['Macas', 'Sucúa', 'Gualaquiza'],
            'Napo' => ['Tena', 'Archidona', 'El Chaco'],
            'Orellana' => ['Francisco de Orellana', 'La Joya de los Sachas'],
            'Pastaza' => ['Puyo', 'Mera', 'Santa Clara'],
            'Pichincha' => [
                'Quito',
                'Cayambe',
                'Mejía',
                'Pedro Moncayo',
                'Rumiñahui',
                'Puerto Quito',
                'San Miguel de Los Bancos',
                'Pedro Vicente Maldonado',
            ],
            'Santa Elena' => ['Santa Elena', 'La Libertad', 'Salinas'],
            'Santo Domingo de los Tsáchilas' => ['Santo Domingo', 'La Concordia'],
            'Sucumbíos' => ['Nueva Loja', 'Shushufindi', 'Cuyabeno'],
            'Tungurahua' => ['Ambato', 'Baños', 'Cevallos', 'Pelileo', 'Píllaro', 'Tisaleo'],
            'Zamora Chinchipe' => ['Zamora', 'Yantzaza', 'Zumba'],
        ];

        foreach ($citiesByProvince as $provinceName => $cities) {
            $province = Province::query()
                ->where('country_id', $ecuador->id)
                ->where('name', $provinceName)
                ->first();

            if (! $province) {
                throw new RuntimeException("Province [{$provinceName}] not found. Run ProvinceSeeder first.");
            }

            foreach ($cities as $cityName) {
                City::query()->updateOrCreate(
                    [
                        'province_id' => $province->id,
                        'name' => $cityName,
                    ],
                    [
                        'active' => true,
                    ]
                );
            }
        }
    }
}
