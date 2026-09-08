<?php

namespace Database\Seeders;

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\Country;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FarmUser;
use App\Models\FlowerType;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PresentationBoxConfig;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;
use App\Services\OrderFulfillmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FarmPortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('iso2', 'EC')->first()
            ?? Country::query()->orderBy('id')->first();

        $farm = Farm::query()->firstOrCreate(
            ['name' => 'Finca Florícola Andina'],
            [
                'commercial_name' => 'Florícola Andina',
                'ruc' => '1799999999001',
                'email' => 'andina@demo.feximar.local',
                'phone' => '0999999999',
                'country_id' => $country?->id,
                'province_id' => null,
                'city_id' => null,
                'address' => 'Calle Demo 123',
                'description' => 'Finca demo para portal de fincas',
                'active' => true,
            ]
        );

        $farmUser = User::query()->firstOrCreate(
            ['email' => 'finca.demo@feximar.local'],
            [
                'name' => 'Manager Finca Andina',
                'password' => Hash::make('FarmDemo123*'),
            ]
        );

        FarmUser::query()->firstOrCreate(
            [
                'farm_id' => $farm->id,
                'user_id' => $farmUser->id,
            ],
            [
                'role' => 'manager',
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
                'price_per_bunch' => 10,
                'active' => true,
            ]
        );

        $full = BoxType::query()->where('code', 'FULL')->first();
        $hb = BoxType::query()->where('code', 'HB')->first();

        if ($full) {
            PresentationBoxConfig::query()->firstOrCreate(
                [
                    'farm_product_presentation_id' => $presentation->id,
                    'box_type_id' => $full->id,
                ],
                [
                    'stems_per_box' => 1600,
                    'bunches_per_box' => null,
                    'active' => true,
                ]
            );
        }

        if ($hb) {
            PresentationBoxConfig::query()->firstOrCreate(
                [
                    'farm_product_presentation_id' => $presentation->id,
                    'box_type_id' => $hb->id,
                ],
                [
                    'stems_per_box' => 200,
                    'bunches_per_box' => null,
                    'active' => true,
                ]
            );
        }

        $availability = FarmProductAvailability::query()->firstOrCreate(
            [
                'farm_product_presentation_id' => $presentation->id,
                'year' => 2026,
                'week_number' => 37,
            ],
            [
                'available_stems' => 5000,
                'reserved_stems' => 0,
                'price_per_stem' => 0.45,
                'price_per_bunch' => 11.25,
                'active' => true,
            ]
        );

        $buyerCountry = $country?->id;

        $buyer = Buyer::query()->firstOrCreate(
            ['email' => 'buyer.demo@feximar.local'],
            [
                'company_name' => 'Sunshine Flowers Imports LLC',
                'contact_name' => 'Demo Buyer',
                'phone' => '+1 555 0000',
                'country_id' => $buyerCountry,
                'country' => $country?->name ?? 'Ecuador',
                'city' => 'Miami',
                'address' => 'Demo Street',
                'active' => true,
            ]
        );

        $existingOrder = Order::query()
            ->where('buyer_id', $buyer->id)
            ->where('notes', 'Pedido demo portal finca')
            ->first();

        if (! $existingOrder && $hb) {
            $order = Order::query()->create([
                'buyer_id' => $buyer->id,
                'status' => 'pending',
                'total' => 900,
                'notes' => 'Pedido demo portal finca',
            ]);

            OrderDetail::query()->create([
                'order_id' => $order->id,
                'farm_product_availability_id' => $availability->id,
                'box_type_id' => $hb->id,
                'boxes' => 5,
                'stems_per_box' => 200,
                'total_stems' => 1000,
                'unit_price' => 180,
                'subtotal' => 900,
            ]);

            app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));
        }

        $this->command?->info('Demo portal finca listo.');
        $this->command?->info('Usuario finca: finca.demo@feximar.local / FarmDemo123*');
        $this->command?->info('Admin existente: test@example.com (sin farm_users → /admin)');
    }
}
