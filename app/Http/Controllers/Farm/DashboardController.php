<?php

namespace App\Http\Controllers\Farm;

use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\OrderFarmFulfillment;
use App\Models\FarmOrderFinance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends BaseFarmController
{
    public function __invoke(Request $request): Response
    {
        $farm = $this->currentFarm($request);
        $now = now();
        $year = (int) $now->isoWeekYear();
        $week = (int) $now->isoWeek();

        $productsActive = FarmProduct::query()
            ->where('farm_id', $farm->id)
            ->where('active', true)
            ->count();

        $stemsAvailable = (int) FarmProductAvailability::query()
            ->where('year', $year)
            ->where('week_number', $week)
            ->where('active', true)
            ->whereHas('presentation.farmProduct', fn ($q) => $q->where('farm_id', $farm->id))
            ->get()
            ->sum(fn (FarmProductAvailability $a) => $a->remainingStems());

        $pendingOrders = OrderFarmFulfillment::query()
            ->where('farm_id', $farm->id)
            ->where('status', 'pending')
            ->count();

        $acceptedOrders = OrderFarmFulfillment::query()
            ->where('farm_id', $farm->id)
            ->where('status', 'accepted')
            ->count();

        $pendingCollection = (float) FarmOrderFinance::query()
            ->where('farm_id', $farm->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->get()
            ->sum(fn (FarmOrderFinance $finance) => $finance->balance());

        return Inertia::render('Farm/Dashboard', [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name,
            ],
            'kpis' => [
                'products_active' => $productsActive,
                'stems_available_week' => $stemsAvailable,
                'orders_pending' => $pendingOrders,
                'orders_accepted' => $acceptedOrders,
                'pending_collection' => round($pendingCollection, 2),
                'current_week' => $week,
                'current_year' => $year,
            ],
        ]);
    }
}
