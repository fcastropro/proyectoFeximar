<?php

namespace App\Services\Admin;

use App\Models\Buyer;
use App\Models\Farm;
use App\Models\FarmProductAvailability;
use App\Models\Order;
use App\Models\OrderFarmFulfillment;
use App\Models\Product;
use App\Services\Admin\UserAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $yearStart = $now->copy()->startOfYear();
        $isoYear = (int) $now->isoWeekYear;
        $isoWeek = (int) $now->isoWeek;

        return [
            'generated_at' => $now->toDateTimeString(),
            'week' => ['year' => $isoYear, 'week' => $isoWeek],
            'commercial' => $this->commercialKpis($monthStart, $yearStart, $now),
            'operations' => $this->operationKpis(),
            'offer' => $this->offerKpis($isoYear, $isoWeek),
            'clients' => $this->clientKpis($monthStart, $now),
            'finance' => $this->financeKpis(),
            'logistics' => $this->logisticsKpis($yearStart, $now),
            'users' => app(UserAccessService::class)->usersDashboardKpis(),
            'alerts' => $this->alerts($isoYear, $isoWeek),
        ];
    }

    /**
     * @return array<string, float|int|null>
     */
    private function commercialKpis(Carbon $monthStart, Carbon $yearStart, Carbon $now): array
    {
        $month = $this->salesAgg($monthStart, $now);
        $year = $this->salesAgg($yearStart, $now);

        return [
            'sales_month' => $month['sales'],
            'sales_year' => $year['sales'],
            'orders_month' => $month['orders'],
            'stems_sold' => $month['stems'],
            'bunches_sold' => $month['bunches'],
            'boxes_required' => $month['boxes'],
            'avg_ticket' => $month['orders'] > 0
                ? round($month['sales'] / $month['orders'], 2)
                : null,
        ];
    }

    /**
     * @return array{sales:float,stems:int,bunches:int,boxes:int,orders:int}
     */
    private function salesAgg(Carbon $from, Carbon $to): array
    {
        $row = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->selectRaw('COALESCE(SUM(od.bunches), 0) as bunches')
            ->selectRaw('COALESCE(SUM(od.boxes), 0) as boxes')
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->first();

        return [
            'sales' => round((float) ($row->sales ?? 0), 2),
            'stems' => (int) ($row->stems ?? 0),
            'bunches' => (int) ($row->bunches ?? 0),
            'boxes' => (int) ($row->boxes ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function operationKpis(): array
    {
        $counts = OrderFarmFulfillment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'accepted' => (int) ($counts['accepted'] ?? 0),
            'preparing' => (int) ($counts['preparing'] ?? 0),
            'ready' => (int) ($counts['ready'] ?? 0),
            'dispatched' => (int) ($counts['dispatched'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function offerKpis(int $year, int $week): array
    {
        $availability = FarmProductAvailability::query()
            ->where('year', $year)
            ->where('week_number', $week)
            ->where('active', true)
            ->selectRaw('COALESCE(SUM(available_stems), 0) as available')
            ->selectRaw('COALESCE(SUM(reserved_stems), 0) as reserved')
            ->first();

        $available = (int) ($availability->available ?? 0);
        $reserved = (int) ($availability->reserved ?? 0);

        return [
            'active_farms' => Farm::query()->where('active', true)->count(),
            'active_products' => Product::query()->where('active', true)->count(),
            'stems_available_week' => $available,
            'stems_reserved' => $reserved,
            'stems_effective' => max(0, $available - $reserved),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clientKpis(Carbon $monthStart, Carbon $now): array
    {
        $top = DB::table('orders as o')
            ->join('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$monthStart->toDateTimeString(), $now->toDateTimeString()])
            ->selectRaw('b.company_name, COALESCE(SUM(o.total), 0) as sales')
            ->groupBy('b.id', 'b.company_name')
            ->orderByDesc('sales')
            ->first();

        return [
            'active_buyers' => Buyer::query()->where('active', true)->count(),
            'new_buyers' => Buyer::query()
                ->whereBetween('created_at', [$monthStart->toDateTimeString(), $now->toDateTimeString()])
                ->count(),
            'top_buyer' => $top?->company_name,
            'top_buyer_sales' => $top ? round((float) $top->sales, 2) : null,
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function financeKpis(): array
    {
        $rows = DB::table('farm_order_finances as fof')
            ->leftJoin('farm_payments as fp', 'fp.farm_order_finance_id', '=', 'fof.id')
            ->selectRaw('fof.id, fof.amount, fof.status, COALESCE(SUM(fp.amount), 0) as paid')
            ->groupBy('fof.id', 'fof.amount', 'fof.status')
            ->get();

        $total = 0.0;
        $paid = 0.0;
        $overdue = 0.0;

        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            $paidAmount = (float) $row->paid;
            $total += $amount;
            $paid += $paidAmount;
            if ($row->status === 'overdue') {
                $overdue += max(0, $amount - $paidAmount);
            }
        }

        $orderPay = Order::query()
            ->where('status', '!=', 'cancelled')
            ->selectRaw("SUM(CASE WHEN payment_condition = 'credit' THEN 1 ELSE 0 END) as credit_orders")
            ->selectRaw("SUM(CASE WHEN payment_condition = 'cash' THEN 1 ELSE 0 END) as cash_orders")
            ->first();

        return [
            'total_receivable' => round($total, 2),
            'total_paid' => round($paid, 2),
            'pending_balance' => round(max(0, $total - $paid), 2),
            'credit_orders' => (int) ($orderPay->credit_orders ?? 0),
            'cash_orders' => (int) ($orderPay->cash_orders ?? 0),
            'overdue_payments' => round($overdue, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function logisticsKpis(Carbon $from, Carbon $to): array
    {
        $shipping = Order::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw("SUM(CASE WHEN shipping_method = 'air' THEN 1 ELSE 0 END) as air")
            ->selectRaw("SUM(CASE WHEN shipping_method = 'sea' THEN 1 ELSE 0 END) as sea")
            ->first();

        $topAgency = DB::table('orders as o')
            ->join('cargo_agencies as ca', 'ca.id', '=', 'o.cargo_agency_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw('ca.name, COUNT(*) as total')
            ->groupBy('ca.id', 'ca.name')
            ->orderByDesc('total')
            ->first();

        $topCountry = DB::table('orders as o')
            ->leftJoin('countries as c', 'c.id', '=', 'o.destination_country_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->selectRaw("COALESCE(c.name, 'Sin país') as name, COUNT(*) as total")
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->first();

        return [
            'air_orders' => (int) ($shipping->air ?? 0),
            'sea_orders' => (int) ($shipping->sea ?? 0),
            'top_cargo_agency' => $topAgency?->name,
            'top_cargo_agency_orders' => $topAgency ? (int) $topAgency->total : null,
            'top_destination_country' => $topCountry?->name,
            'top_destination_orders' => $topCountry ? (int) $topCountry->total : null,
        ];
    }

    /**
     * @return list<array{type:string,message:string,count:int}>
     */
    private function alerts(int $year, int $week): array
    {
        $alerts = [];

        $pendingAccept = OrderFarmFulfillment::query()->where('status', 'pending')->count();
        if ($pendingAccept > 0) {
            $alerts[] = [
                'type' => 'fulfillment_pending',
                'message' => "{$pendingAccept} pedidos pendientes de aceptación de finca",
                'count' => $pendingAccept,
            ];
        }

        $overdueCount = DB::table('farm_order_finances')->where('status', 'overdue')->count();
        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'payment_overdue',
                'message' => "{$overdueCount} pagos vencidos a fincas",
                'count' => $overdueCount,
            ];
        }

        $creditDueSoon = DB::table('farm_order_finances')
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [Carbon::today()->toDateString(), Carbon::today()->addDays(7)->toDateString()])
            ->count();
        if ($creditDueSoon > 0) {
            $alerts[] = [
                'type' => 'credit_due_soon',
                'message' => "{$creditDueSoon} créditos próximos a vencer (7 días)",
                'count' => $creditDueSoon,
            ];
        }

        $lowAvailability = FarmProductAvailability::query()
            ->where('year', $year)
            ->where('week_number', $week)
            ->where('active', true)
            ->whereRaw('(available_stems - reserved_stems) > 0')
            ->whereRaw('(available_stems - reserved_stems) < 200')
            ->count();
        if ($lowAvailability > 0) {
            $alerts[] = [
                'type' => 'low_availability',
                'message' => "{$lowAvailability} disponibilidades bajas esta semana (< 200 tallos)",
                'count' => $lowAvailability,
            ];
        }

        $noPrice = FarmProductAvailability::query()
            ->where('year', $year)
            ->where('week_number', $week)
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('price_per_stem')->orWhere('price_per_stem', '<=', 0);
            })
            ->count();
        if ($noPrice > 0) {
            $alerts[] = [
                'type' => 'missing_price',
                'message' => "{$noPrice} disponibilidades sin precio semanal",
                'count' => $noPrice,
            ];
        }

        $noImage = Product::query()
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('image_path')->orWhere('image_path', '');
            })
            ->count();
        if ($noImage > 0) {
            $alerts[] = [
                'type' => 'missing_image',
                'message' => "{$noImage} productos sin fotografía",
                'count' => $noImage,
            ];
        }

        $noBoxConfig = DB::table('farm_product_presentations as fpp')
            ->leftJoin('presentation_box_configs as pbc', 'pbc.farm_product_presentation_id', '=', 'fpp.id')
            ->where('fpp.active', true)
            ->whereNull('pbc.id')
            ->count();
        if ($noBoxConfig > 0) {
            $alerts[] = [
                'type' => 'missing_box_config',
                'message' => "{$noBoxConfig} presentaciones sin configuración de caja",
                'count' => $noBoxConfig,
            ];
        }

        $farmsWithOffer = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fpa.year', $year)
            ->where('fpa.week_number', $week)
            ->where('fpa.active', true)
            ->distinct()
            ->pluck('fp.farm_id');

        $farmsWithoutOffer = Farm::query()
            ->where('active', true)
            ->whereNotIn('id', $farmsWithOffer)
            ->count();
        if ($farmsWithoutOffer > 0) {
            $alerts[] = [
                'type' => 'farm_no_offer',
                'message' => "{$farmsWithoutOffer} fincas no reportaron disponibilidad esta semana",
                'count' => $farmsWithoutOffer,
            ];
        }

        $readyNotDispatched = OrderFarmFulfillment::query()
            ->where('status', 'ready')
            ->count();
        if ($readyNotDispatched > 0) {
            $alerts[] = [
                'type' => 'ready_not_dispatched',
                'message' => "{$readyNotDispatched} pedidos listos pero no despachados",
                'count' => $readyNotDispatched,
            ];
        }

        $userAccess = app(UserAccessService::class);
        $inactiveUsers = $userAccess->inactiveUsersCount();
        if ($inactiveUsers > 0) {
            $alerts[] = [
                'type' => 'inactive_users',
                'message' => "{$inactiveUsers} usuarios inactivos",
                'count' => $inactiveUsers,
            ];
        }

        $invalidProfile = $userAccess->usersWithoutValidProfileCount();
        if ($invalidProfile > 0) {
            $alerts[] = [
                'type' => 'invalid_profile',
                'message' => "{$invalidProfile} usuarios sin perfil válido",
                'count' => $invalidProfile,
            ];
        }

        return $alerts;
    }
}
