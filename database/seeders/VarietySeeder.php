<?php

namespace Database\Seeders;

use App\Models\FlowerType;
use App\Models\Variety;
use Illuminate\Database\Seeder;
use RuntimeException;

class VarietySeeder extends Seeder
{
    public function run(): void
    {
        $varietiesByType = [
            'Rosa' => [
                ['name' => 'Freedom', 'color' => 'Rojo'],
                ['name' => 'Mondial', 'color' => 'Blanco'],
                ['name' => 'Explorer', 'color' => 'Rojo'],
                ['name' => 'Playa Blanca', 'color' => 'Blanco'],
                ['name' => 'High & Magic', 'color' => 'Bicolor'],
                ['name' => 'Red Naomi', 'color' => 'Rojo'],
                ['name' => 'Pink Floyd', 'color' => 'Rosado'],
                ['name' => 'Vendela', 'color' => 'Crema'],
                ['name' => 'Avalanche', 'color' => 'Blanco'],
                ['name' => 'Upper Class', 'color' => 'Rosado'],
            ],
            'Clavel' => [
                ['name' => 'Don Pedro', 'color' => 'Rojo'],
                ['name' => 'Baltico', 'color' => 'Blanco'],
                ['name' => 'Nobbio', 'color' => 'Rosado'],
                ['name' => 'Kiwi', 'color' => 'Verde'],
            ],
            'Gypsophila' => [
                ['name' => 'Million Stars', 'color' => 'Blanco'],
                ['name' => 'Xlence', 'color' => 'Blanco'],
                ['name' => 'Mirabella', 'color' => 'Blanco'],
                ['name' => 'Overtime', 'color' => 'Blanco'],
            ],
            'Alstroemeria' => [
                ['name' => 'Cairo', 'color' => 'Naranja'],
                ['name' => 'Virginia', 'color' => 'Blanco'],
                ['name' => 'Pink Panther', 'color' => 'Rosado'],
                ['name' => 'Yellow King', 'color' => 'Amarillo'],
            ],
            'Crisantemo' => [
                ['name' => 'Anastasia', 'color' => 'Blanco'],
                ['name' => 'Pina Colada', 'color' => 'Amarillo'],
                ['name' => 'Balloon', 'color' => 'Verde'],
                ['name' => 'Magnum', 'color' => 'Blanco'],
            ],
            'Lirio' => [
                ['name' => 'Sorbonne', 'color' => 'Rosado'],
                ['name' => 'Tiber', 'color' => 'Blanco'],
                ['name' => 'Brunello', 'color' => 'Naranja'],
                ['name' => 'Conca d\'Or', 'color' => 'Amarillo'],
            ],
            'Gerbera' => [
                ['name' => 'Terra Fame', 'color' => 'Naranja'],
                ['name' => 'Dino', 'color' => 'Amarillo'],
                ['name' => 'Kimsey', 'color' => 'Rosado'],
                ['name' => 'Iceberg', 'color' => 'Blanco'],
            ],
            'Girasol' => [
                ['name' => 'Sunrich Orange', 'color' => 'Naranja'],
                ['name' => 'Pro Cut White Nite', 'color' => 'Blanco'],
                ['name' => 'Starburst Lemon Aura', 'color' => 'Amarillo'],
            ],
            'Hortensia' => [
                ['name' => 'Magical Revolution', 'color' => 'Rosado'],
                ['name' => 'Classic Early Blue', 'color' => 'Azul'],
                ['name' => 'Snowball', 'color' => 'Blanco'],
            ],
            'Lisianthus' => [
                ['name' => 'Arena White', 'color' => 'Blanco'],
                ['name' => 'Mariachi Pink', 'color' => 'Rosado'],
                ['name' => 'ABC Purple', 'color' => 'Morado'],
            ],
            'Delphinium' => [
                ['name' => 'Guardian Blue', 'color' => 'Azul'],
                ['name' => 'Guardian White', 'color' => 'Blanco'],
                ['name' => 'Guardian Lavender', 'color' => 'Lavanda'],
            ],
            'Statice' => [
                ['name' => 'QIS Purple', 'color' => 'Morado'],
                ['name' => 'QIS White', 'color' => 'Blanco'],
                ['name' => 'QIS Yellow', 'color' => 'Amarillo'],
            ],
            'Limonium' => [
                ['name' => 'Sinensis Blue', 'color' => 'Azul'],
                ['name' => 'Sinensis White', 'color' => 'Blanco'],
                ['name' => 'Emille', 'color' => 'Lavanda'],
            ],
            'Solidago' => [
                ['name' => 'Tara', 'color' => 'Amarillo'],
                ['name' => 'Yellow Submarine', 'color' => 'Amarillo'],
            ],
            'Hypericum' => [
                ['name' => 'Coco Extra', 'color' => 'Rojo'],
                ['name' => 'Dolly Magenta', 'color' => 'Magenta'],
                ['name' => 'Pink Attraction', 'color' => 'Rosado'],
            ],
            'Snapdragon' => [
                ['name' => 'Potomac White', 'color' => 'Blanco'],
                ['name' => 'Potomac Pink', 'color' => 'Rosado'],
                ['name' => 'Potomac Yellow', 'color' => 'Amarillo'],
            ],
            'Calla' => [
                ['name' => 'Captain Ventura', 'color' => 'Blanco'],
                ['name' => 'Picasso', 'color' => 'Bicolor'],
                ['name' => 'Flame', 'color' => 'Naranja'],
            ],
            'Orquídea' => [
                ['name' => 'Phalaenopsis White', 'color' => 'Blanco'],
                ['name' => 'Cymbidium Green', 'color' => 'Verde'],
                ['name' => 'Dendrobium Pink', 'color' => 'Rosado'],
            ],
            'Tulipán' => [
                ['name' => 'Strong Gold', 'color' => 'Amarillo'],
                ['name' => 'Purple Prince', 'color' => 'Morado'],
                ['name' => 'White Dream', 'color' => 'Blanco'],
            ],
            'Eucalipto' => [
                ['name' => 'Baby Blue', 'color' => 'Verde'],
                ['name' => 'Gunni', 'color' => 'Verde'],
                ['name' => 'Cinerea', 'color' => 'Verde'],
            ],
            'Ruscus' => [
                ['name' => 'Israeli Ruscus', 'color' => 'Verde'],
                ['name' => 'Italian Ruscus', 'color' => 'Verde'],
            ],
            'Leather Leaf' => [
                ['name' => 'Standard Leather Leaf', 'color' => 'Verde'],
            ],
        ];

        foreach ($varietiesByType as $flowerTypeName => $varieties) {
            $flowerType = FlowerType::query()->where('name', $flowerTypeName)->first();

            if (! $flowerType) {
                throw new RuntimeException(
                    "Flower type [{$flowerTypeName}] not found. Run FlowerTypeSeeder first."
                );
            }

            foreach ($varieties as $variety) {
                Variety::query()->updateOrCreate(
                    [
                        'flower_type_id' => $flowerType->id,
                        'name' => $variety['name'],
                    ],
                    [
                        'color' => $variety['color'],
                        'active' => true,
                    ]
                );
            }
        }
    }
}
