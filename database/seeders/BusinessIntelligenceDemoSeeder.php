<?php

namespace Database\Seeders;

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\Country;
use App\Models\Farm;
use App\Models\FarmOrderFinance;
use App\Models\FarmPayment;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FlowerType;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PresentationBoxConfig;
use App\Models\Product;
use App\Models\Variety;
use App\Services\OrderFulfillmentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * SOLO desarrollo/local. NO incluir en DatabaseSeeder ni producción.
 *
 * php artisan db:seed --class=BusinessIntelligenceDemoSeeder
 */
class BusinessIntelligenceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $countryEc = Country::query()->where('iso2', 'EC')->first()
            ?? Country::query()->orderBy('id')->first();
        $countryUs = Country::query()->where('iso2', 'US')->first()
            ?? Country::query()->where('name', 'like', '%United%')->first()
            ?? $countryEc;

        $farm = Farm::query()->firstOrCreate(
            ['name' => 'Finca Florícola Andina'],
            [
                'commercial_name' => 'Florícola Andina',
                'email' => 'andina@demo.feximar.local',
                'country_id' => $countryEc?->id,
                'active' => true,
            ]
        );

        $farmB = Farm::query()->firstOrCreate(
            ['name' => 'Finca Valle del Sol'],
            [
                'commercial_name' => 'Valle del Sol',
                'email' => 'valle@demo.feximar.local',
                'country_id' => $countryEc?->id,
                'active' => true,
            ]
        );

        $flowerType = FlowerType::query()->firstOrCreate(['name' => 'Rosa'], ['active' => true]);
        $freedom = Variety::query()->firstOrCreate(
            ['flower_type_id' => $flowerType->id, 'name' => 'Freedom'],
            ['color' => 'Rojo', 'active' => true]
        );
        $explorer = Variety::query()->firstOrCreate(
            ['flower_type_id' => $flowerType->id, 'name' => 'Explorer'],
            ['color' => 'Rojo', 'active' => true]
        );

        $productFreedom = Product::query()->firstOrCreate(
            ['name' => 'Rosa Freedom'],
            ['variety_id' => $freedom->id, 'category' => 'Rosa', 'variety' => 'Freedom', 'color' => 'Rojo', 'active' => true]
        );
        $productExplorer = Product::query()->firstOrCreate(
            ['name' => 'Rosa Explorer'],
            ['variety_id' => $explorer->id, 'category' => 'Rosa', 'variety' => 'Explorer', 'color' => 'Rojo', 'active' => true]
        );

        $fpFreedom = FarmProduct::query()->firstOrCreate(
            ['farm_id' => $farm->id, 'product_id' => $productFreedom->id],
            ['active' => true]
        );
        $fpExplorer = FarmProduct::query()->firstOrCreate(
            ['farm_id' => $farmB->id, 'product_id' => $productExplorer->id],
            ['active' => true]
        );

        $presFreedom = FarmProductPresentation::query()->firstOrCreate(
            ['farm_product_id' => $fpFreedom->id, 'stem_length_cm' => 50],
            ['stems_per_bunch' => 25, 'active' => true]
        );
        $presExplorer = FarmProductPresentation::query()->firstOrCreate(
            ['farm_product_id' => $fpExplorer->id, 'stem_length_cm' => 60],
            ['stems_per_bunch' => 25, 'active' => true]
        );

        $hb = BoxType::query()->firstOrCreate(['code' => 'HB'], ['name' => 'Half Box', 'active' => true]);
        $full = BoxType::query()->firstOrCreate(['code' => 'FULL'], ['name' => 'Full Box', 'active' => true]);

        PresentationBoxConfig::query()->firstOrCreate(
            ['farm_product_presentation_id' => $presFreedom->id, 'box_type_id' => $hb->id],
            ['stems_per_box' => 200, 'active' => true]
        );
        PresentationBoxConfig::query()->firstOrCreate(
            ['farm_product_presentation_id' => $presExplorer->id, 'box_type_id' => $full->id],
            ['stems_per_box' => 1600, 'active' => true]
        );

        $buyers = [
            Buyer::query()->firstOrCreate(
                ['email' => 'buyer.bi.us@feximar.local'],
                [
                    'company_name' => 'Sunshine Flowers Imports LLC',
                    'contact_name' => 'BI Buyer US',
                    'country_id' => $countryUs?->id,
                    'country' => $countryUs?->name ?? 'United States',
                    'active' => true,
                ]
            ),
            Buyer::query()->firstOrCreate(
                ['email' => 'buyer.bi.eu@feximar.local'],
                [
                    'company_name' => 'Amsterdam Floral Trade',
                    'contact_name' => 'BI Buyer EU',
                    'country_id' => $countryEc?->id,
                    'country' => 'Netherlands',
                    'active' => true,
                ]
            ),
        ];

        $fulfillment = app(OrderFulfillmentService::class);

        foreach ([2024, 2025, 2026] as $year) {
            for ($month = 1; $month <= ($year === 2026 ? 9 : 12); $month++) {
                $week = (int) Carbon::create($year, $month, 15)->isoWeek();
                $isoYear = (int) Carbon::create($year, $month, 15)->isoWeekYear();

                $availFreedom = FarmProductAvailability::query()->firstOrCreate(
                    [
                        'farm_product_presentation_id' => $presFreedom->id,
                        'year' => $isoYear,
                        'week_number' => $week,
                    ],
                    [
                        'available_stems' => 8000 + ($month * 100),
                        'reserved_stems' => 0,
                        'price_per_stem' => 0.40 + ($month * 0.01),
                        'active' => true,
                    ]
                );

                $availExplorer = FarmProductAvailability::query()->firstOrCreate(
                    [
                        'farm_product_presentation_id' => $presExplorer->id,
                        'year' => $isoYear,
                        'week_number' => $week,
                    ],
                    [
                        'available_stems' => 6000 + ($month * 80),
                        'reserved_stems' => 0,
                        'price_per_stem' => 0.38 + ($month * 0.008),
                        'active' => true,
                    ]
                );

                $buyer = $buyers[($month + $year) % 2];
                $createdAt = Carbon::create($year, $month, min(28, 5 + ($month % 20)), 10, 0, 0);

                $marker = "BI-DEMO-{$year}-{$month}";
                $existing = Order::query()->where('notes', $marker)->first();
                if ($existing) {
                    continue;
                }

                $boxesHb = 3 + ($month % 5);
                $boxesFull = 1 + ($month % 3);
                $priceHb = 160 + $month * 2;
                $priceFull = 700 + $month * 5;
                $subtotal = ($boxesHb * $priceHb) + ($boxesFull * $priceFull);

                $order = Order::query()->create([
                    'buyer_id' => $buyer->id,
                    'status' => $month % 7 === 0 ? 'shipped' : 'confirmed',
                    'total' => $subtotal,
                    'notes' => $marker,
                ]);
                $order->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();

                $d1 = OrderDetail::query()->create([
                    'order_id' => $order->id,
                    'farm_product_availability_id' => $availFreedom->id,
                    'box_type_id' => $hb->id,
                    'boxes' => $boxesHb,
                    'stems_per_box' => 200,
                    'total_stems' => $boxesHb * 200,
                    'unit_price' => $priceHb,
                    'subtotal' => $boxesHb * $priceHb,
                ]);
                $d1->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();

                $d2 = OrderDetail::query()->create([
                    'order_id' => $order->id,
                    'farm_product_availability_id' => $availExplorer->id,
                    'box_type_id' => $full->id,
                    'boxes' => $boxesFull,
                    'stems_per_box' => 1600,
                    'total_stems' => $boxesFull * 1600,
                    'unit_price' => $priceFull,
                    'subtotal' => $boxesFull * $priceFull,
                ]);
                $d2->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();

                $fulfillment->syncForOrder($order->fresh('details'));

                $finance = FarmOrderFinance::query()->firstOrCreate(
                    ['order_id' => $order->id, 'farm_id' => $farm->id],
                    [
                        'amount' => $boxesHb * $priceHb,
                        'payment_condition' => $month % 2 === 0 ? 'credit' : 'cash',
                        'credit_days' => $month % 2 === 0 ? 30 : null,
                        'due_date' => $month % 2 === 0 ? $createdAt->copy()->addDays(30)->toDateString() : null,
                        'status' => 'pending',
                    ]
                );
                $finance->forceFill([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->saveQuietly();

                if ($month % 3 !== 0) {
                    $payDate = $createdAt->copy()->addDays(10 + ($month % 15));
                    $payment = FarmPayment::query()->firstOrCreate(
                        [
                            'farm_order_finance_id' => $finance->id,
                            'reference' => "BI-PAY-{$year}-{$month}",
                        ],
                        [
                            'amount' => round(((float) $finance->amount) * ($month % 3 === 1 ? 1 : 0.5), 2),
                            'payment_date' => $payDate->toDateString(),
                            'payment_method' => 'transfer',
                        ]
                    );
                    $payment->forceFill([
                        'created_at' => $payDate,
                        'updated_at' => $payDate,
                    ])->saveQuietly();
                    $finance->refreshStatus();
                }
            }
        }

        $this->command?->info('BusinessIntelligenceDemoSeeder listo (2024-2026). No usar en producción.');
    }
}
