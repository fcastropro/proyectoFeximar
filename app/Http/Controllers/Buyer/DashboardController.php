<?php

namespace App\Http\Controllers\Buyer;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseBuyerController
{
    public function __invoke(Request $request): Response
    {
        $buyer = $this->currentBuyer($request);

        $orders = Order::query()->where('buyer_id', $buyer->id);

        $ordersCount = (clone $orders)->count();
        $inProgress = (clone $orders)->whereIn('status', ['pending', 'confirmed', 'processing'])->count();
        $completed = (clone $orders)->whereIn('status', ['shipped'])->count();
        $totalPurchases = (float) (clone $orders)->where('status', '!=', 'cancelled')->sum('total');

        $stemsBought = (int) DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->where('o.buyer_id', $buyer->id)
            ->where('o.status', '!=', 'cancelled')
            ->sum('od.total_stems');

        $topVariety = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('farm_product_availabilities as fpa', 'fpa.id', '=', 'od.farm_product_availability_id')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->where('o.buyer_id', $buyer->id)
            ->where('o.status', '!=', 'cancelled')
            ->selectRaw("COALESCE(NULLIF(v.name, ''), NULLIF(p.variety, ''), p.name) as variety")
            ->selectRaw('SUM(od.total_stems) as stems')
            ->groupByRaw("COALESCE(NULLIF(v.name, ''), NULLIF(p.variety, ''), p.name)")
            ->orderByDesc('stems')
            ->first();

        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $purchasesByMonth = DB::table('orders')
            ->where('buyer_id', $buyer->id)
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("{$monthExpr} as period")
            ->selectRaw('COALESCE(SUM(total), 0) as total')
            ->groupByRaw($monthExpr)
            ->orderBy('period')
            ->get();

        $stemsByVariety = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('farm_product_availabilities as fpa', 'fpa.id', '=', 'od.farm_product_availability_id')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->where('o.buyer_id', $buyer->id)
            ->where('o.status', '!=', 'cancelled')
            ->selectRaw("COALESCE(NULLIF(v.name, ''), NULLIF(p.variety, ''), p.name) as variety")
            ->selectRaw('SUM(od.total_stems) as stems')
            ->groupByRaw("COALESCE(NULLIF(v.name, ''), NULLIF(p.variety, ''), p.name)")
            ->orderByDesc('stems')
            ->limit(8)
            ->get();

        return Inertia::render('Buyer/Dashboard', [
            'kpis' => [
                'orders' => $ordersCount,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'total_purchases' => round($totalPurchases, 2),
                'stems_bought' => $stemsBought,
                'top_variety' => $topVariety?->variety,
            ],
            'charts' => [
                'purchases_by_month' => [
                    'labels' => $purchasesByMonth->pluck('period')->all(),
                    'data' => $purchasesByMonth->map(fn ($r) => round((float) $r->total, 2))->all(),
                ],
                'stems_by_variety' => [
                    'labels' => $stemsByVariety->pluck('variety')->all(),
                    'data' => $stemsByVariety->map(fn ($r) => (int) $r->stems)->all(),
                ],
            ],
        ]);
    }
}
