<?php

namespace Database\Seeders;

use App\Models\FlowerType;
use Illuminate\Database\Seeder;

class FlowerTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Rosa',
            'Clavel',
            'Gypsophila',
            'Alstroemeria',
            'Crisantemo',
            'Lirio',
            'Gerbera',
            'Girasol',
            'Hortensia',
            'Lisianthus',
            'Delphinium',
            'Statice',
            'Limonium',
            'Solidago',
            'Hypericum',
            'Snapdragon',
            'Calla',
            'Orquídea',
            'Tulipán',
            'Eucalipto',
            'Ruscus',
            'Leather Leaf',
        ];

        foreach ($types as $name) {
            FlowerType::query()->updateOrCreate(
                ['name' => $name],
                ['active' => true]
            );
        }
    }
}
