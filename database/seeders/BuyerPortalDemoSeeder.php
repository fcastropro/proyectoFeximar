<?php

namespace Database\Seeders;

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\BuyerUser;
use App\Models\Country;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FlowerType;
use App\Models\PresentationBoxConfig;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo local del portal de compradores.
 * NO incluir en DatabaseSeeder / producción.
 *
 * php artisan db:seed --class=BuyerPortalDemoSeeder
 */
class BuyerPortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('iso2', 'US')->first()
            ?? Country::query()->orderBy('id')->first();

        $buyer = Buyer::query()->firstOrCreate(
            ['email' => 'orders@sunshineflowers.demo'],
            [
                'company_name' => 'Sunshine Flowers Imports LLC',
                'contact_name' => 'Buyer Demo Manager',
                'phone' => '+1 305 555 0100',
                'country_id' => $country?->id,
                'country' => $country?->name ?? 'United States',
                'city' => 'Miami',
                'address' => '1200 Demo Ave',
                'active' => true,
                'credit_allowed' => true,
                'credit_days_default' => 30,
            ]
        );

        $buyer->forceFill([
            'credit_allowed' => true,
            'credit_days_default' => 30,
            'active' => true,
        ])->save();

        $user = User::query()->firstOrCreate(
            ['email' => 'buyer.demo@feximar.local'],
            [
                'name' => 'Buyer Demo Manager',
                'password' => Hash::make('BuyerDemo123*'),
            ]
        );

        BuyerUser::query()->firstOrCreate(
            [
                'buyer_id' => $buyer->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'manager',
                'active' => true,
            ]
        );

        $farm = Farm::query()->firstOrCreate(
            ['name' => 'Finca Florícola Andina'],
            [
                'commercial_name' => 'Florícola Andina',
                'active' => true,
            ]
        );

        $flowerType = FlowerType::query()->firstOrCreate(
            ['name' => 'Rosa'],
            ['active' => true]
        );

        $variety = Variety::query()->firstOrCreate(
            [
                'flower_type_id' => $flowerType->id,
                'name' => 'Freedom',
            ],
            [
                'color' => 'Rojo',
                'active' => true,
            ]
        );

        $product = Product::query()->firstOrCreate(
            ['name' => 'Rosa Freedom'],
            [
                'variety_id' => $variety->id,
                'category' => 'Rosa',
                'variety' => 'Freedom',
                'color' => 'Rojo',
                'active' => true,
            ]
        );

        $farmProduct = FarmProduct::query()->firstOrCreate(
            [
                'farm_id' => $farm->id,
                'product_id' => $product->id,
            ],
            ['active' => true]
        );

        $presentation = FarmProductPresentation::query()->firstOrCreate(
            [
                'farm_product_id' => $farmProduct->id,
                'stem_length_cm' => 50,
            ],
            [
                'stems_per_bunch' => 25,
                'price_per_stem' => 0.40,
                'active' => true,
            ]
        );

        foreach (['FULL', 'HB'] as $code) {
            $box = BoxType::query()->where('code', $code)->first();
            if (! $box) {
                continue;
            }

            PresentationBoxConfig::query()->firstOrCreate(
                [
                    'farm_product_presentation_id' => $presentation->id,
                    'box_type_id' => $box->id,
                ],
                [
                    'stems_per_box' => $code === 'FULL' ? 400 : 200,
                    'bunches_per_box' => $code === 'FULL' ? 16 : 8,
                    'active' => true,
                ]
            );
        }

        $now = Carbon::now();

        FarmProductAvailability::query()->firstOrCreate(
            [
                'farm_product_presentation_id' => $presentation->id,
                'year' => (int) $now->isoWeekYear(),
                'week_number' => (int) $now->isoWeek(),
            ],
            [
                'available_stems' => 8000,
                'reserved_stems' => 0,
                'price_per_stem' => 0.42,
                'active' => true,
            ]
        );

        \App\Models\CargoAgency::query()->firstOrCreate(
            ['code' => 'AG1'],
            [
                'name' => 'Agencia de carga 1',
                'contact_name' => 'Contacto Demo 1',
                'active' => true,
            ]
        );

        \App\Models\CargoAgency::query()->firstOrCreate(
            ['code' => 'AG2'],
            [
                'name' => 'Agencia de carga 2',
                'contact_name' => 'Contacto Demo 2',
                'active' => true,
            ]
        );

        $presentation->forceFill(['stems_per_bunch' => 25])->save();
    }
}
