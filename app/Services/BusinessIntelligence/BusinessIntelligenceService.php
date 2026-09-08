<?php

namespace App\Services\BusinessIntelligence;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessIntelligenceService
{
    /**
     * @return array{
     *   date_from:?string,
     *   date_to:?string,
     *   year:?int,
     *   month:?int,
     *   week:?int,
     *   farm_id:?int,
     *   country_id:?int,
     *   buyer_id:?int,
     *   flower_type_id:?int,
     *   variety_id:?int,
     *   stem_length_cm:?int,
     *   order_status:?string,
     *   variety_filter_id:?int
     * }
     */
    public function filtersFromRequest(Request $request): array
    {
        return [
            'date_from' => $request->filled('date_from') ? $request->string('date_from')->toString() : null,
            'date_to' => $request->filled('date_to') ? $request->string('date_to')->toString() : null,
            'year' => $request->filled('year') ? $request->integer('year') : null,
            'month' => $request->filled('month') ? $request->integer('month') : null,
            'week' => $request->filled('week') ? $request->integer('week') : null,
            'farm_id' => $request->filled('farm_id') ? $request->integer('farm_id') : null,
            'country_id' => $request->filled('country_id') ? $request->integer('country_id') : null,
            'buyer_id' => $request->filled('buyer_id') ? $request->integer('buyer_id') : null,
            'flower_type_id' => $request->filled('flower_type_id') ? $request->integer('flower_type_id') : null,
            'variety_id' => $request->filled('variety_id') ? $request->integer('variety_id') : null,
            'stem_length_cm' => $request->filled('stem_length_cm') ? $request->integer('stem_length_cm') : null,
            'order_status' => $request->filled('order_status') ? $request->string('order_status')->toString() : null,
            'variety_filter_id' => $request->filled('variety_filter_id') ? $request->integer('variety_filter_id') : null,
            'shipping_method' => $request->filled('shipping_method') ? $request->string('shipping_method')->toString() : null,
            'cargo_agency_id' => $request->filled('cargo_agency_id') ? $request->integer('cargo_agency_id') : null,
            'payment_condition' => $request->filled('payment_condition') ? $request->string('payment_condition')->toString() : null,
            'product_id' => $request->filled('product_id') ? $request->integer('product_id') : null,
        ];
    }

    /**
     * @return array{from:Carbon,to:Carbon}
     */
    public function resolvePeriod(array $filters): array
    {
        if ($filters['date_from'] || $filters['date_to']) {
            $from = $filters['date_from']
                ? Carbon::parse($filters['date_from'])->startOfDay()
                : Carbon::parse($filters['date_to'])->startOfYear();
            $to = $filters['date_to']
                ? Carbon::parse($filters['date_to'])->endOfDay()
                : Carbon::now()->endOfDay();

            return ['from' => $from, 'to' => $to];
        }

        if ($filters['year'] && $filters['week']) {
            $from = Carbon::now()->setISODate((int) $filters['year'], (int) $filters['week'])->startOfWeek();
            $to = (clone $from)->endOfWeek();

            return ['from' => $from, 'to' => $to];
        }

        if ($filters['year'] && $filters['month']) {
            $from = Carbon::create((int) $filters['year'], (int) $filters['month'], 1)->startOfMonth();
            $to = (clone $from)->endOfMonth();

            return ['from' => $from, 'to' => $to];
        }

        if ($filters['year']) {
            $from = Carbon::create((int) $filters['year'], 1, 1)->startOfYear();
            $to = (clone $from)->endOfYear();

            return ['from' => $from, 'to' => $to];
        }

        $from = Carbon::now()->startOfYear();
        $to = Carbon::now()->endOfDay();

        return ['from' => $from, 'to' => $to];
    }

    /**
     * @return array{from:Carbon,to:Carbon}
     */
    public function previousPeriod(Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;

        return [
            'from' => (clone $from)->subDays($days)->startOfDay(),
            'to' => (clone $from)->subDay()->endOfDay(),
        ];
    }

    public function filterOptions(): array
    {
        return [
            'farms' => DB::table('farms')->orderBy('name')->get(['id', 'name']),
            'countries' => DB::table('countries')->orderBy('name')->get(['id', 'name']),
            'buyers' => DB::table('buyers')->orderBy('company_name')->get(['id', 'company_name']),
            'flower_types' => DB::table('flower_types')->orderBy('name')->get(['id', 'name']),
            'varieties' => DB::table('varieties')->orderBy('name')->get(['id', 'name', 'flower_type_id']),
            'stem_lengths' => DB::table('farm_product_presentations')
                ->select('stem_length_cm')
                ->distinct()
                ->orderBy('stem_length_cm')
                ->pluck('stem_length_cm'),
            'order_statuses' => [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'cancelled',
            ],
            'shipping_methods' => ['air', 'sea'],
            'payment_conditions' => ['cash', 'credit'],
            'cargo_agencies' => DB::table('cargo_agencies')->orderBy('name')->get(['id', 'name']),
            'products' => DB::table('products')->orderBy('name')->get(['id', 'name']),
            'years' => range((int) date('Y') - 3, (int) date('Y')),
        ];
    }

    /**
     * Base query for order detail lines with catalog joins.
     */
    public function baseDetailsQuery(array $filters, Carbon $from, Carbon $to)
    {
        $query = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->leftJoin('countries as c', 'c.id', '=', 'b.country_id')
            ->join('farm_product_availabilities as fpa', 'fpa.id', '=', 'od.farm_product_availability_id')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('farms as f', 'f.id', '=', 'fp.farm_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->leftJoin('flower_types as ft', 'ft.id', '=', 'v.flower_type_id')
            ->leftJoin('box_types as bt', 'bt.id', '=', 'od.box_type_id')
            ->whereBetween('o.created_at', [$from->toDateTimeString(), $to->toDateTimeString()]);

        if (! empty($filters['order_status'])) {
            $query->where('o.status', $filters['order_status']);
        } else {
            $query->where('o.status', '!=', 'cancelled');
        }

        if (! empty($filters['farm_id'])) {
            $query->where('f.id', $filters['farm_id']);
        }
        if (! empty($filters['country_id'])) {
            $query->where('b.country_id', $filters['country_id']);
        }
        if (! empty($filters['buyer_id'])) {
            $query->where('b.id', $filters['buyer_id']);
        }
        if (! empty($filters['flower_type_id'])) {
            $query->where('ft.id', $filters['flower_type_id']);
        }
        if (! empty($filters['variety_id'])) {
            $query->where('v.id', $filters['variety_id']);
        }
        if (! empty($filters['stem_length_cm'])) {
            $query->where('fpp.stem_length_cm', $filters['stem_length_cm']);
        }
        if (! empty($filters['shipping_method'])) {
            $query->where('o.shipping_method', $filters['shipping_method']);
        }
        if (! empty($filters['cargo_agency_id'])) {
            $query->where('o.cargo_agency_id', $filters['cargo_agency_id']);
        }
        if (! empty($filters['payment_condition'])) {
            $query->where('o.payment_condition', $filters['payment_condition']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('p.id', $filters['product_id']);
        }

        return $query;
    }

    public function varietyExpression(): string
    {
        return "COALESCE(NULLIF(v.name, ''), NULLIF(p.variety, ''), p.name, 'Sin variedad')";
    }

    public function countryExpression(): string
    {
        return "COALESCE(NULLIF(c.name, ''), NULLIF(b.country, ''), 'Sin país')";
    }

    public function monthExpression(string $column = 'o.created_at'): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    public function monthStartExpression(string $column = 'o.created_at'): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-01', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m-01')";
    }

    /**
     * @return array<string, mixed>
     */
    public function executiveKpis(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $prev = $this->previousPeriod($period['from'], $period['to']);

        $current = $this->periodTotals($filters, $period['from'], $period['to']);
        $previous = $this->periodTotals($filters, $prev['from'], $prev['to']);

        $pendingCollection = $this->pendingReceivables($filters, $period['from'], $period['to']);

        return [
            'period' => [
                'from' => $period['from']->toDateString(),
                'to' => $period['to']->toDateString(),
            ],
            'previous_period' => [
                'from' => $prev['from']->toDateString(),
                'to' => $prev['to']->toDateString(),
            ],
            'kpis' => [
                $this->kpi('sales', 'Ventas totales', $current['sales'], $previous['sales'], 'currency'),
                $this->kpi('stems', 'Tallos vendidos', $current['stems'], $previous['stems'], 'number'),
                $this->kpi('boxes', 'Cajas vendidas', $current['boxes'], $previous['boxes'], 'number'),
                $this->kpi('orders', 'Total pedidos', $current['orders'], $previous['orders'], 'number'),
                $this->kpi('avg_ticket', 'Ticket promedio', $current['avg_ticket'], $previous['avg_ticket'], 'currency'),
                $this->kpi('pending_collection', 'Valor pendiente de cobro', $pendingCollection['current'], $pendingCollection['previous'], 'currency'),
                $this->kpi('active_buyers', 'Compradores activos', $current['buyers'], $previous['buyers'], 'number'),
                $this->kpi('active_farms', 'Fincas activas', $current['farms'], $previous['farms'], 'number'),
            ],
        ];
    }

    /**
     * @return array{sales:float,stems:int,boxes:int,orders:int,avg_ticket:float,buyers:int,farms:int}
     */
    public function periodTotals(array $filters, Carbon $from, Carbon $to): array
    {
        $row = $this->baseDetailsQuery($filters, $from, $to)
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->selectRaw('COALESCE(SUM(od.boxes), 0) as boxes')
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw('COUNT(DISTINCT o.buyer_id) as buyers')
            ->selectRaw('COUNT(DISTINCT f.id) as farms')
            ->first();

        $sales = (float) ($row->sales ?? 0);
        $orders = (int) ($row->orders ?? 0);

        return [
            'sales' => $sales,
            'stems' => (int) ($row->stems ?? 0),
            'boxes' => (int) ($row->boxes ?? 0),
            'orders' => $orders,
            'avg_ticket' => $orders > 0 ? round($sales / $orders, 2) : 0.0,
            'buyers' => (int) ($row->buyers ?? 0),
            'farms' => (int) ($row->farms ?? 0),
        ];
    }

    /**
     * @return array{current:float,previous:float}
     */
    public function pendingReceivables(array $filters, Carbon $from, Carbon $to): array
    {
        $current = $this->financeBalanceSum($filters, $from, $to);
        $prev = $this->previousPeriod($from, $to);

        return [
            'current' => $current,
            'previous' => $this->financeBalanceSum($filters, $prev['from'], $prev['to']),
        ];
    }

    private function financeBalanceSum(array $filters, Carbon $from, Carbon $to): float
    {
        $query = DB::table('farm_order_finances as fof')
            ->join('orders as o', 'o.id', '=', 'fof.order_id')
            ->leftJoin('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->whereBetween('fof.created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->whereIn('fof.status', ['pending', 'partial', 'overdue']);

        if (! empty($filters['farm_id'])) {
            $query->where('fof.farm_id', $filters['farm_id']);
        }
        if (! empty($filters['buyer_id'])) {
            $query->where('o.buyer_id', $filters['buyer_id']);
        }
        if (! empty($filters['country_id'])) {
            $query->where('b.country_id', $filters['country_id']);
        }
        if (! empty($filters['order_status'])) {
            $query->where('o.status', $filters['order_status']);
        }

        $finances = $query->get(['fof.id', 'fof.amount']);

        if ($finances->isEmpty()) {
            return 0.0;
        }

        $paid = DB::table('farm_payments')
            ->whereIn('farm_order_finance_id', $finances->pluck('id'))
            ->selectRaw('farm_order_finance_id, COALESCE(SUM(amount), 0) as paid')
            ->groupBy('farm_order_finance_id')
            ->pluck('paid', 'farm_order_finance_id');

        $balance = 0.0;
        foreach ($finances as $finance) {
            $balance += max(0, (float) $finance->amount - (float) ($paid[$finance->id] ?? 0));
        }

        return round($balance, 2);
    }

    /**
     * @return array{key:string,label:string,value:float|int,previous:float|int,variation:float|null,format:string}
     */
    private function kpi(string $key, string $label, float|int $value, float|int $previous, string $format): array
    {
        $variation = null;
        if ((float) $previous != 0.0) {
            $variation = round((((float) $value - (float) $previous) / (float) $previous) * 100, 1);
        } elseif ((float) $value > 0 && (float) $previous == 0.0) {
            $variation = null; // no comparable base
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $format === 'currency' ? round((float) $value, 2) : (int) round((float) $value),
            'previous' => $format === 'currency' ? round((float) $previous, 2) : (int) round((float) $previous),
            'variation' => $variation,
            'format' => $format,
            'has_comparison' => (float) $previous != 0.0 || ((float) $value == 0.0 && (float) $previous == 0.0),
        ];
    }

    public function salesByMonth(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $rows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw($this->monthExpression().' as period')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($this->monthExpression())
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $labels = [];
        $data = [];
        foreach (CarbonPeriod::create($period['from']->copy()->startOfMonth(), '1 month', $period['to']->copy()->startOfMonth()) as $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat('M Y');
            $data[] = round((float) ($rows[$key]->sales ?? 0), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function stemsByVariety(array $filters, int $limit = 15): array
    {
        return $this->rankBy($filters, $this->varietyExpression(), 'stems', $limit);
    }

    public function salesByVariety(array $filters, int $limit = 15): array
    {
        return $this->rankBy($filters, $this->varietyExpression(), 'sales', $limit);
    }

    public function topBuyers(array $filters, int $limit = 10): array
    {
        return $this->rankBy($filters, 'b.company_name', 'sales', $limit, true);
    }

    public function salesByCountry(array $filters): array
    {
        return $this->rankBy($filters, $this->countryExpression(), 'sales', 20);
    }

    public function salesByFarm(array $filters): array
    {
        return $this->rankBy($filters, 'f.name', 'sales', 20);
    }

    public function boxesByType(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $rows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("COALESCE(bt.code, 'N/D') as label")
            ->selectRaw('COALESCE(SUM(od.boxes), 0) as value')
            ->groupBy('label')
            ->orderByDesc('value')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->map(fn ($r) => (int) $r->value)->all(),
        ];
    }

    public function avgStemPriceByMonth(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $varietyExpr = $this->varietyExpression();
        $monthExpr = $this->monthExpression();

        $labels = [];
        $monthKeys = [];
        foreach (CarbonPeriod::create($period['from']->copy()->startOfMonth(), '1 month', $period['to']->copy()->startOfMonth()) as $month) {
            $labels[] = $month->translatedFormat('M Y');
            $monthKeys[] = $month->format('Y-m');
        }

        $overallRows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$monthExpr} as period")
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->groupByRaw($monthExpr)
            ->get()
            ->keyBy('period');

        $overallData = [];
        foreach ($monthKeys as $key) {
            $sales = (float) ($overallRows[$key]->sales ?? 0);
            $stems = (int) ($overallRows[$key]->stems ?? 0);
            $overallData[] = $stems > 0 ? round($sales / $stems, 4) : 0;
        }

        $topVarieties = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->groupByRaw($varietyExpr)
            ->orderByDesc('stems')
            ->limit(5)
            ->pluck('variety');

        if ($topVarieties->isEmpty()) {
            return ['labels' => $labels, 'data' => $overallData, 'datasets' => []];
        }

        $varietyRows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw("{$monthExpr} as period")
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->groupByRaw("{$varietyExpr}, {$monthExpr}")
            ->get()
            ->filter(fn ($row) => $topVarieties->contains($row->variety))
            ->groupBy('variety');

        $datasets = [];
        foreach ($topVarieties as $variety) {
            $byPeriod = ($varietyRows[$variety] ?? collect())->keyBy('period');
            $data = [];
            foreach ($monthKeys as $key) {
                $sales = (float) ($byPeriod[$key]->sales ?? 0);
                $stems = (int) ($byPeriod[$key]->stems ?? 0);
                $data[] = $stems > 0 ? round($sales / $stems, 4) : 0;
            }
            $datasets[] = ['label' => $variety, 'data' => $data];
        }

        return [
            'labels' => $labels,
            'data' => $overallData,
            'datasets' => $datasets,
        ];
    }

    private function rankBy(array $filters, string $labelExpr, string $metric, int $limit, bool $horizontalFriendly = false): array
    {
        $period = $this->resolvePeriod($filters);
        $valueExpr = match ($metric) {
            'stems' => 'COALESCE(SUM(od.total_stems), 0)',
            'boxes' => 'COALESCE(SUM(od.boxes), 0)',
            'bunches' => 'COALESCE(SUM(od.bunches), 0)',
            default => 'COALESCE(SUM(od.subtotal), 0)',
        };

        $rows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$labelExpr} as label")
            ->selectRaw("{$valueExpr} as value")
            ->groupByRaw($labelExpr)
            ->orderByDesc('value')
            ->limit($limit)
            ->get();

        if ($horizontalFriendly) {
            $rows = $rows->reverse()->values();
        }

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->map(fn ($r) => in_array($metric, ['sales'], true)
                ? round((float) $r->value, 2)
                : (int) $r->value)->all(),
        ];
    }

    public function historicalVarietyTable(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $prev = $this->previousPeriod($period['from'], $period['to']);
        $varietyExpr = $this->varietyExpression();

        $current = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->selectRaw('COALESCE(SUM(od.boxes), 0) as boxes')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($varietyExpr)
            ->get()
            ->keyBy('variety');

        $previous = $this->baseDetailsQuery($filters, $prev['from'], $prev['to'])
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($varietyExpr)
            ->pluck('sales', 'variety');

        $totalSales = (float) $current->sum(fn ($r) => (float) $r->sales);

        return $current->map(function ($row) use ($previous, $totalSales) {
            $sales = (float) $row->sales;
            $stems = (int) $row->stems;
            $prevSales = (float) ($previous[$row->variety] ?? 0);
            $growth = $prevSales > 0
                ? round((($sales - $prevSales) / $prevSales) * 100, 1)
                : null;

            return [
                'variety' => $row->variety,
                'stems' => $stems,
                'boxes' => (int) $row->boxes,
                'sales' => round($sales, 2),
                'avg_price' => $stems > 0 ? round($sales / $stems, 4) : 0,
                'share' => $totalSales > 0 ? round(($sales / $totalSales) * 100, 1) : 0,
                'growth' => $growth,
            ];
        })->sortByDesc('sales')->values()->all();
    }

    public function currentAvailabilityByVariety(array $filters): array
    {
        $now = Carbon::now();
        $year = (int) $now->isoWeekYear();
        $week = (int) $now->isoWeek();

        $availability = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->leftJoin('flower_types as ft', 'ft.id', '=', 'v.flower_type_id')
            ->where('fpa.year', $year)
            ->where('fpa.week_number', $week)
            ->where('fpa.active', true);

        if (! empty($filters['farm_id'])) {
            $availability->where('fp.farm_id', $filters['farm_id']);
        }
        if (! empty($filters['flower_type_id'])) {
            $availability->where('ft.id', $filters['flower_type_id']);
        }
        if (! empty($filters['variety_id'])) {
            $availability->where('v.id', $filters['variety_id']);
        }
        if (! empty($filters['stem_length_cm'])) {
            $availability->where('fpp.stem_length_cm', $filters['stem_length_cm']);
        }

        $varietyExpr = $this->varietyExpression();

        $rows = $availability
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('COALESCE(SUM(fpa.available_stems), 0) as available_stems')
            ->selectRaw('COALESCE(SUM(fpa.reserved_stems), 0) as reserved_stems')
            ->selectRaw('AVG(fpa.price_per_stem) as avg_price')
            ->groupByRaw($varietyExpr)
            ->get()
            ->keyBy('variety');

        $periodFrom = $now->copy()->startOfWeek();
        $periodTo = $now->copy()->endOfWeek();

        $orders = $this->baseDetailsQuery($filters, $periodFrom, $periodTo)
            ->join('order_farm_fulfillments as off', function ($join) {
                $join->on('off.order_id', '=', 'o.id')
                    ->on('off.farm_id', '=', 'f.id');
            })
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw("SUM(CASE WHEN off.status = 'pending' THEN od.total_stems ELSE 0 END) as pending_stems")
            ->selectRaw("SUM(CASE WHEN off.status = 'accepted' THEN od.total_stems ELSE 0 END) as accepted_stems")
            ->groupByRaw($varietyExpr)
            ->get()
            ->keyBy('variety');

        $keys = $rows->keys()->merge($orders->keys())->unique()->sort()->values();

        return [
            'week' => $week,
            'year' => $year,
            'rows' => $keys->map(function ($variety) use ($rows, $orders) {
                $a = $rows[$variety] ?? null;
                $o = $orders[$variety] ?? null;
                $available = (int) ($a->available_stems ?? 0);
                $reserved = (int) ($a->reserved_stems ?? 0);

                return [
                    'variety' => $variety,
                    'available_stems' => $available,
                    'reserved_stems' => $reserved,
                    'effective_stems' => max(0, $available - $reserved),
                    'current_price' => $a?->avg_price !== null ? round((float) $a->avg_price, 4) : null,
                    'pending_stems' => (int) ($o->pending_stems ?? 0),
                    'accepted_stems' => (int) ($o->accepted_stems ?? 0),
                ];
            })->values()->all(),
        ];
    }

    public function trends(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $insufficient = 'No existe información histórica suficiente para calcular este indicador.';

        $currentMonthFrom = $period['to']->copy()->startOfMonth();
        $currentMonthTo = $period['to']->copy()->endOfMonth();
        $prevMonthFrom = $currentMonthFrom->copy()->subMonth()->startOfMonth();
        $prevMonthTo = $currentMonthFrom->copy()->subMonth()->endOfMonth();

        $monthNow = $this->periodTotals($filters, $currentMonthFrom, $currentMonthTo);
        $monthPrev = $this->periodTotals($filters, $prevMonthFrom, $prevMonthTo);

        $yearNowFrom = $period['to']->copy()->startOfYear();
        $yearNowTo = $period['to']->copy()->endOfDay();
        $yearPrevFrom = $yearNowFrom->copy()->subYear();
        $yearPrevTo = $yearNowTo->copy()->subYear();
        $yearNow = $this->periodTotals($filters, $yearNowFrom, $yearNowTo);
        $yearPrev = $this->periodTotals($filters, $yearPrevFrom, $yearPrevTo);

        $avgNow = $monthNow['stems'] > 0 ? $monthNow['sales'] / $monthNow['stems'] : 0;
        $avgPrev = $monthPrev['stems'] > 0 ? $monthPrev['sales'] / $monthPrev['stems'] : 0;

        $growthMonthly = $this->pctChange($monthNow['sales'], $monthPrev['sales']);
        $growthAnnual = $this->pctChange($yearNow['sales'], $yearPrev['sales']);
        $stemsVar = $this->pctChange($monthNow['stems'], $monthPrev['stems']);
        $priceVar = $this->pctChange($avgNow, $avgPrev);

        $varietyGrowth = $this->varietyGrowthRanking($filters, $currentMonthFrom, $currentMonthTo, $prevMonthFrom, $prevMonthTo);

        return [
            'indicators' => [
                [
                    'key' => 'monthly_sales_growth',
                    'label' => 'Crecimiento mensual de ventas',
                    'value' => $growthMonthly,
                    'message' => $growthMonthly === null ? $insufficient : null,
                ],
                [
                    'key' => 'annual_sales_growth',
                    'label' => 'Crecimiento anual de ventas',
                    'value' => $growthAnnual,
                    'message' => $growthAnnual === null ? $insufficient : null,
                ],
                [
                    'key' => 'stems_variation',
                    'label' => 'Variación de tallos vendidos (mes)',
                    'value' => $stemsVar,
                    'message' => $stemsVar === null ? $insufficient : null,
                ],
                [
                    'key' => 'avg_price_variation',
                    'label' => 'Variación de precio promedio por tallo (mes)',
                    'value' => $priceVar,
                    'message' => $priceVar === null ? $insufficient : null,
                ],
            ],
            'top_growing_varieties' => $varietyGrowth['growing'],
            'least_movement_varieties' => $varietyGrowth['least'],
            'top_buyers_share' => $this->buyerShare($filters, $period['from'], $period['to']),
            'insufficient_message' => $insufficient,
        ];
    }

    private function pctChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous == 0.0) {
            return null;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }

    private function varietyGrowthRanking(
        array $filters,
        Carbon $from,
        Carbon $to,
        Carbon $prevFrom,
        Carbon $prevTo
    ): array {
        $expr = $this->varietyExpression();
        $now = $this->baseDetailsQuery($filters, $from, $to)
            ->selectRaw("{$expr} as variety")
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($expr)
            ->pluck('sales', 'variety');

        $prev = $this->baseDetailsQuery($filters, $prevFrom, $prevTo)
            ->selectRaw("{$expr} as variety")
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($expr)
            ->pluck('sales', 'variety');

        $growing = [];
        $least = [];

        foreach ($now as $variety => $sales) {
            $prevSales = (float) ($prev[$variety] ?? 0);
            $change = $this->pctChange((float) $sales, $prevSales);
            $item = [
                'variety' => $variety,
                'sales' => round((float) $sales, 2),
                'previous_sales' => round($prevSales, 2),
                'growth' => $change,
            ];

            if ($change !== null && $change > 0) {
                $growing[] = $item;
            }
            if ((float) $sales >= 0) {
                $least[] = $item;
            }
        }

        usort($growing, fn ($a, $b) => ($b['growth'] ?? -999) <=> ($a['growth'] ?? -999));
        usort($least, fn ($a, $b) => ($a['sales'] <=> $b['sales']));

        return [
            'growing' => array_slice($growing, 0, 10),
            'least' => array_slice($least, 0, 10),
        ];
    }

    private function buyerShare(array $filters, Carbon $from, Carbon $to): array
    {
        $rows = $this->baseDetailsQuery($filters, $from, $to)
            ->selectRaw('b.company_name as buyer')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupBy('b.company_name')
            ->orderByDesc('sales')
            ->limit(10)
            ->get();

        $total = (float) $rows->sum('sales');

        return $rows->map(fn ($r) => [
            'buyer' => $r->buyer,
            'sales' => round((float) $r->sales, 2),
            'share' => $total > 0 ? round(((float) $r->sales / $total) * 100, 1) : 0,
        ])->all();
    }

    public function financialTraceability(array $filters): array
    {
        $period = $this->resolvePeriod($filters);

        $query = DB::table('farm_order_finances as fof')
            ->join('orders as o', 'o.id', '=', 'fof.order_id')
            ->leftJoin('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->whereBetween('fof.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()]);

        if (! empty($filters['farm_id'])) {
            $query->where('fof.farm_id', $filters['farm_id']);
        }
        if (! empty($filters['buyer_id'])) {
            $query->where('o.buyer_id', $filters['buyer_id']);
        }
        if (! empty($filters['country_id'])) {
            $query->where('b.country_id', $filters['country_id']);
        }

        $finances = $query->get([
            'fof.id',
            'fof.amount',
            'fof.payment_condition',
            'fof.status',
            'fof.created_at',
        ]);

        $cash = 0.0;
        $credit = 0.0;
        foreach ($finances as $finance) {
            if ($finance->payment_condition === 'cash') {
                $cash += (float) $finance->amount;
            } else {
                $credit += (float) $finance->amount;
            }
        }

        $ids = $finances->pluck('id');
        $paidByFinance = $ids->isEmpty()
            ? collect()
            : DB::table('farm_payments')
                ->whereIn('farm_order_finance_id', $ids)
                ->selectRaw('farm_order_finance_id, COALESCE(SUM(amount), 0) as paid, MAX(payment_date) as last_payment')
                ->groupBy('farm_order_finance_id')
                ->get()
                ->keyBy('farm_order_finance_id');

        $collected = 0.0;
        $pending = 0.0;
        $overdue = 0.0;
        $paymentDays = [];

        foreach ($finances as $finance) {
            $paid = (float) ($paidByFinance[$finance->id]->paid ?? 0);
            $balance = max(0, (float) $finance->amount - $paid);
            $collected += $paid;
            $pending += $balance;

            if ($finance->status === 'overdue') {
                $overdue += $balance;
            }

            if ($paid + 0.0001 >= (float) $finance->amount && ! empty($paidByFinance[$finance->id]->last_payment)) {
                $start = Carbon::parse($finance->created_at)->startOfDay();
                $end = Carbon::parse($paidByFinance[$finance->id]->last_payment)->startOfDay();
                $paymentDays[] = $start->diffInDays($end);
            }
        }

        $avgPaymentDays = count($paymentDays) > 0
            ? round(array_sum($paymentDays) / count($paymentDays), 1)
            : null;

        return [
            'cash_sales' => round($cash, 2),
            'credit_sales' => round($credit, 2),
            'accounts_receivable' => round($pending, 2),
            'collected' => round($collected, 2),
            'pending_balance' => round($pending, 2),
            'overdue_amount' => round($overdue, 2),
            'avg_payment_days' => $avgPaymentDays,
            'avg_payment_days_message' => $avgPaymentDays === null
                ? 'No existe información suficiente para calcular este indicador.'
                : null,
            'collected_vs_pending' => [
                'labels' => ['Cobrado', 'Pendiente'],
                'data' => [round($collected, 2), round($pending, 2)],
            ],
            'notes' => [
                'Las métricas financieras se basan en farm_order_finances / farm_payments (pago FEXIMAR → finca), no en cobros al comprador internacional.',
            ],
        ];
    }

    /**
     * Dataset histórico reutilizable para futuros modelos de predicción.
     *
     * @return Collection<int, object>
     */
    public function predictionDataset(?Carbon $from = null, ?Carbon $to = null, ?int $farmId = null): Collection
    {
        $from ??= Carbon::create(2020, 1, 1);
        $to ??= Carbon::now();

        $query = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->leftJoin('countries as c', 'c.id', '=', 'b.country_id')
            ->join('farm_product_availabilities as fpa', 'fpa.id', '=', 'od.farm_product_availability_id')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('farms as f', 'f.id', '=', 'fp.farm_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$from->toDateTimeString(), $to->toDateTimeString()]);

        if ($farmId) {
            $query->where('f.id', $farmId);
        }

        return $query
            ->selectRaw($this->monthStartExpression().' as period_date')
            ->selectRaw($this->monthExpression().' as period')
            ->selectRaw("{$this->varietyExpression()} as variety")
            ->selectRaw('fpp.stem_length_cm as stem_length_cm')
            ->selectRaw('f.name as farm')
            ->selectRaw('b.company_name as buyer')
            ->selectRaw("{$this->countryExpression()} as country")
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems_sold')
            ->selectRaw('CASE WHEN SUM(od.total_stems) > 0 THEN SUM(od.subtotal) / SUM(od.total_stems) ELSE 0 END as avg_stem_price')
            ->selectRaw('COUNT(DISTINCT o.id) as orders_count')
            ->selectRaw('COALESCE(AVG(fpa.available_stems), 0) as availability')
            ->groupByRaw(
                $this->monthStartExpression().', '.$this->monthExpression()
                .", {$this->varietyExpression()}, fpp.stem_length_cm, f.name, b.company_name, {$this->countryExpression()}"
            )
            ->orderBy('period_date')
            ->get();
    }

    /**
     * Filtros para portal finca: farm_id SIEMPRE forzado desde backend.
     */
    public function filtersForFarm(Request $request, int $farmId): array
    {
        $filters = $this->filtersFromRequest($request);
        $filters['farm_id'] = $farmId;

        return $filters;
    }

    public function filterOptionsForFarm(int $farmId): array
    {
        $options = $this->filterOptions();
        unset($options['farms']);

        $options['stem_lengths'] = DB::table('farm_product_presentations as fpp')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fp.farm_id', $farmId)
            ->select('fpp.stem_length_cm')
            ->distinct()
            ->orderBy('fpp.stem_length_cm')
            ->pluck('stem_length_cm');

        return $options;
    }

    public function farmExecutiveKpis(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $prev = $this->previousPeriod($period['from'], $period['to']);
        $current = $this->periodTotals($filters, $period['from'], $period['to']);
        $previous = $this->periodTotals($filters, $prev['from'], $prev['to']);
        $fulfillments = $this->fulfillmentStatusCounts($filters, $period['from'], $period['to']);
        $fulfillmentsPrev = $this->fulfillmentStatusCounts($filters, $prev['from'], $prev['to']);
        $finance = $this->financialTraceability($filters);
        $financePrevPeriod = $this->pendingReceivables($filters, $period['from'], $period['to']);
        $collectedPrev = $this->collectedSum($filters, $prev['from'], $prev['to']);

        $activeProducts = (int) DB::table('farm_products')
            ->where('farm_id', $filters['farm_id'])
            ->where('active', true)
            ->count();

        $currentStems = $this->currentEffectiveStems((int) $filters['farm_id']);

        return [
            'period' => [
                'from' => $period['from']->toDateString(),
                'to' => $period['to']->toDateString(),
            ],
            'previous_period' => [
                'from' => $prev['from']->toDateString(),
                'to' => $prev['to']->toDateString(),
            ],
            'kpis' => [
                $this->kpi('sales', 'Ventas totales', $current['sales'], $previous['sales'], 'currency'),
                $this->kpi('stems', 'Tallos vendidos', $current['stems'], $previous['stems'], 'number'),
                $this->kpi('boxes', 'Cajas vendidas', $current['boxes'], $previous['boxes'], 'number'),
                $this->kpi('orders_received', 'Pedidos recibidos', $fulfillments['total'], $fulfillmentsPrev['total'], 'number'),
                $this->kpi('orders_accepted', 'Pedidos aceptados', $fulfillments['accepted'], $fulfillmentsPrev['accepted'], 'number'),
                $this->kpi('orders_rejected', 'Pedidos rechazados', $fulfillments['rejected'], $fulfillmentsPrev['rejected'], 'number'),
                $this->kpi('avg_ticket', 'Ticket promedio', $current['avg_ticket'], $previous['avg_ticket'], 'currency'),
                $this->kpi('pending_collection', 'Valor pendiente de cobro', $finance['pending_balance'], $financePrevPeriod['previous'], 'currency'),
                $this->kpi('collected', 'Total cobrado', $finance['collected'], $collectedPrev, 'currency'),
                [
                    'key' => 'avg_payment_days',
                    'label' => 'Días promedio de pago',
                    'value' => $finance['avg_payment_days'] ?? 0,
                    'previous' => 0,
                    'variation' => null,
                    'format' => 'number',
                    'has_comparison' => false,
                    'message' => $finance['avg_payment_days_message'],
                ],
                $this->kpi('active_products', 'Productos activos', $activeProducts, $activeProducts, 'number'),
                $this->kpi('current_stems', 'Tallos disponibles actualmente', $currentStems, $currentStems, 'number'),
            ],
        ];
    }

    /**
     * @return array{total:int,pending:int,accepted:int,rejected:int}
     */
    public function fulfillmentStatusCounts(array $filters, Carbon $from, Carbon $to): array
    {
        $query = DB::table('order_farm_fulfillments as off')
            ->where('off.farm_id', $filters['farm_id'])
            ->whereBetween('off.created_at', [$from->toDateTimeString(), $to->toDateTimeString()]);

        $rows = $query
            ->selectRaw('off.status, COUNT(*) as total')
            ->groupBy('off.status')
            ->pluck('total', 'status');

        $pending = (int) ($rows['pending'] ?? 0);
        $accepted = (int) ($rows['accepted'] ?? 0)
            + (int) ($rows['preparing'] ?? 0)
            + (int) ($rows['ready'] ?? 0)
            + (int) ($rows['dispatched'] ?? 0)
            + (int) ($rows['completed'] ?? 0);
        $rejected = (int) ($rows['rejected'] ?? 0);

        return [
            'total' => (int) $rows->sum(),
            'pending' => $pending,
            'accepted' => $accepted,
            'rejected' => $rejected,
        ];
    }

    public function currentEffectiveStems(int $farmId): int
    {
        $now = Carbon::now();

        $row = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fp.farm_id', $farmId)
            ->where('fpa.year', (int) $now->isoWeekYear())
            ->where('fpa.week_number', (int) $now->isoWeek())
            ->where('fpa.active', true)
            ->selectRaw('COALESCE(SUM(fpa.available_stems - fpa.reserved_stems), 0) as effective')
            ->first();

        return max(0, (int) ($row->effective ?? 0));
    }

    private function collectedSum(array $filters, Carbon $from, Carbon $to): float
    {
        $query = DB::table('farm_payments as fp')
            ->join('farm_order_finances as fof', 'fof.id', '=', 'fp.farm_order_finance_id')
            ->where('fof.farm_id', $filters['farm_id'])
            ->whereBetween('fp.payment_date', [$from->toDateString(), $to->toDateString()]);

        return round((float) $query->sum('fp.amount'), 2);
    }

    public function farmHistoricalTable(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $prev = $this->previousPeriod($period['from'], $period['to']);
        $varietyExpr = $this->varietyExpression();

        $current = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('fpp.stem_length_cm as stem_length_cm')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->selectRaw('COALESCE(SUM(od.boxes), 0) as boxes')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->groupByRaw("{$varietyExpr}, fpp.stem_length_cm")
            ->get();

        $previous = $this->baseDetailsQuery($filters, $prev['from'], $prev['to'])
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('fpp.stem_length_cm as stem_length_cm')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw("{$varietyExpr}, fpp.stem_length_cm")
            ->get()
            ->keyBy(fn ($r) => $r->variety.'|'.$r->stem_length_cm);

        $totalSales = (float) $current->sum(fn ($r) => (float) $r->sales);

        return $current->map(function ($row) use ($previous, $totalSales) {
            $key = $row->variety.'|'.$row->stem_length_cm;
            $sales = (float) $row->sales;
            $stems = (int) $row->stems;
            $prevSales = (float) ($previous[$key]->sales ?? 0);

            return [
                'variety' => $row->variety,
                'stem_length_cm' => (int) $row->stem_length_cm,
                'stems' => $stems,
                'boxes' => (int) $row->boxes,
                'sales' => round($sales, 2),
                'avg_price' => $stems > 0 ? round($sales / $stems, 4) : 0,
                'orders' => (int) $row->orders,
                'share' => $totalSales > 0 ? round(($sales / $totalSales) * 100, 1) : 0,
                'growth' => $prevSales > 0 ? round((($sales - $prevSales) / $prevSales) * 100, 1) : null,
            ];
        })->sortByDesc('sales')->values()->all();
    }

    public function farmCurrentOperations(array $filters): array
    {
        $now = Carbon::now();
        $year = (int) $now->isoWeekYear();
        $week = (int) $now->isoWeek();
        $varietyExpr = $this->varietyExpression();

        $availability = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->leftJoin('flower_types as ft', 'ft.id', '=', 'v.flower_type_id')
            ->where('fp.farm_id', $filters['farm_id'])
            ->where('fpa.year', $year)
            ->where('fpa.week_number', $week)
            ->where('fpa.active', true);

        if (! empty($filters['flower_type_id'])) {
            $availability->where('ft.id', $filters['flower_type_id']);
        }
        if (! empty($filters['variety_id'])) {
            $availability->where('v.id', $filters['variety_id']);
        }
        if (! empty($filters['stem_length_cm'])) {
            $availability->where('fpp.stem_length_cm', $filters['stem_length_cm']);
        }

        $rows = $availability
            ->selectRaw('p.name as product_name')
            ->selectRaw("{$varietyExpr} as variety")
            ->selectRaw('fpp.stem_length_cm as stem_length_cm')
            ->selectRaw('fpa.id as availability_id')
            ->selectRaw('fpa.available_stems')
            ->selectRaw('fpa.reserved_stems')
            ->selectRaw('fpa.price_per_stem')
            ->orderBy('p.name')
            ->get();

        $periodFrom = $now->copy()->startOfWeek();
        $periodTo = $now->copy()->endOfWeek();

        $orderStats = $this->baseDetailsQuery($filters, $periodFrom, $periodTo)
            ->leftJoin('order_farm_fulfillments as off', function ($join) {
                $join->on('off.order_id', '=', 'o.id')
                    ->on('off.farm_id', '=', 'f.id');
            })
            ->selectRaw('od.farm_product_availability_id')
            ->selectRaw("SUM(CASE WHEN off.status = 'pending' THEN od.total_stems ELSE 0 END) as pending_stems")
            ->selectRaw("SUM(CASE WHEN off.status IN ('accepted','preparing','ready','dispatched','completed') THEN od.total_stems ELSE 0 END) as accepted_stems")
            ->groupBy('od.farm_product_availability_id')
            ->get()
            ->keyBy('farm_product_availability_id');

        return [
            'week' => $week,
            'year' => $year,
            'rows' => $rows->map(function ($row) use ($orderStats) {
                $stats = $orderStats[$row->availability_id] ?? null;
                $available = (int) $row->available_stems;
                $reserved = (int) $row->reserved_stems;

                return [
                    'product_name' => $row->product_name,
                    'variety' => $row->variety,
                    'stem_length_cm' => (int) $row->stem_length_cm,
                    'available_stems' => $available,
                    'reserved_stems' => $reserved,
                    'effective_stems' => max(0, $available - $reserved),
                    'current_price' => $row->price_per_stem !== null ? round((float) $row->price_per_stem, 4) : null,
                    'pending_stems' => (int) ($stats->pending_stems ?? 0),
                    'accepted_stems' => (int) ($stats->accepted_stems ?? 0),
                ];
            })->values()->all(),
        ];
    }

    public function availabilityVsSoldByWeek(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $farmId = (int) $filters['farm_id'];

        $availability = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fp.farm_id', $farmId)
            ->where(function ($q) use ($period) {
                $q->whereBetween('fpa.year', [$period['from']->isoWeekYear(), $period['to']->isoWeekYear()]);
            })
            ->selectRaw('fpa.year, fpa.week_number')
            ->selectRaw('COALESCE(SUM(fpa.available_stems), 0) as reported')
            ->selectRaw('COALESCE(SUM(fpa.reserved_stems), 0) as reserved')
            ->groupBy('fpa.year', 'fpa.week_number')
            ->orderBy('fpa.year')
            ->orderBy('fpa.week_number')
            ->get()
            ->keyBy(fn ($r) => $r->year.'-'.$r->week_number);

        $sold = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw('fpa.year, fpa.week_number')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as sold')
            ->groupBy('fpa.year', 'fpa.week_number')
            ->get()
            ->keyBy(fn ($r) => $r->year.'-'.$r->week_number);

        $keys = $availability->keys()->merge($sold->keys())->unique()->sort()->values();

        $labels = [];
        $reported = [];
        $reserved = [];
        $effective = [];
        $soldSeries = [];

        foreach ($keys as $key) {
            $a = $availability[$key] ?? null;
            $s = $sold[$key] ?? null;
            $rep = (int) ($a->reported ?? 0);
            $res = (int) ($a->reserved ?? 0);
            $labels[] = 'S'.($a->week_number ?? $s->week_number).'/'.($a->year ?? $s->year);
            $reported[] = $rep;
            $reserved[] = $res;
            $effective[] = max(0, $rep - $res);
            $soldSeries[] = (int) ($s->sold ?? 0);
        }

        return [
            'labels' => $labels,
            'reported' => $reported,
            'reserved' => $reserved,
            'effective' => $effective,
            'sold' => $soldSeries,
        ];
    }

    public function yearOverYear(array $filters, array $years = [2024, 2025, 2026]): array
    {
        $labels = array_map('strval', $years);
        $sales = [];
        $stems = [];
        $boxes = [];
        $orders = [];

        foreach ($years as $year) {
            $scoped = array_merge($filters, [
                'year' => $year,
                'date_from' => null,
                'date_to' => null,
                'month' => null,
                'week' => null,
            ]);
            $period = $this->resolvePeriod($scoped);
            $totals = $this->periodTotals($scoped, $period['from'], $period['to']);

            $sales[] = $totals['sales'];
            $stems[] = $totals['stems'];
            $boxes[] = $totals['boxes'];
            $orders[] = $totals['orders'];
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'stems' => $stems,
            'boxes' => $boxes,
            'orders' => $orders,
        ];
    }

    public function farmTrends(array $filters): array
    {
        $base = $this->trends($filters);
        $period = $this->resolvePeriod($filters);

        $base['top_countries_share'] = $this->countryShare($filters, $period['from'], $period['to']);
        $base['stems_demand'] = $this->stemsByMonth($filters);

        return $base;
    }

    private function countryShare(array $filters, Carbon $from, Carbon $to): array
    {
        $rows = $this->baseDetailsQuery($filters, $from, $to)
            ->selectRaw($this->countryExpression().' as country')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($this->countryExpression())
            ->orderByDesc('sales')
            ->limit(10)
            ->get();

        $total = (float) $rows->sum('sales');

        return $rows->map(fn ($r) => [
            'country' => $r->country,
            'sales' => round((float) $r->sales, 2),
            'share' => $total > 0 ? round(((float) $r->sales / $total) * 100, 1) : 0,
        ])->all();
    }

    public function stemsByMonth(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $rows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw($this->monthExpression().' as period')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->groupByRaw($this->monthExpression())
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $labels = [];
        $data = [];
        foreach (CarbonPeriod::create($period['from']->copy()->startOfMonth(), '1 month', $period['to']->copy()->startOfMonth()) as $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat('M Y');
            $data[] = (int) ($rows[$key]->stems ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function salesByYear(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $yearExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y', o.created_at)"
            : 'YEAR(o.created_at)';

        $rows = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw("{$yearExpr} as period")
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->groupByRaw($yearExpr)
            ->orderBy('period')
            ->get();

        return [
            'labels' => $rows->pluck('period')->map(fn ($y) => (string) $y)->all(),
            'data' => $rows->map(fn ($r) => round((float) $r->sales, 2))->all(),
        ];
    }

    public function bunchesByVariety(array $filters, int $limit = 15): array
    {
        return $this->rankBy($filters, $this->varietyExpression(), 'bunches', $limit);
    }

    public function ordersByStatus(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $query = DB::table('orders as o')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()]);

        if (! empty($filters['buyer_id'])) {
            $query->where('o.buyer_id', $filters['buyer_id']);
        }
        if (! empty($filters['shipping_method'])) {
            $query->where('o.shipping_method', $filters['shipping_method']);
        }
        if (! empty($filters['payment_condition'])) {
            $query->where('o.payment_condition', $filters['payment_condition']);
        }
        if (! empty($filters['cargo_agency_id'])) {
            $query->where('o.cargo_agency_id', $filters['cargo_agency_id']);
        }

        $rows = $query
            ->selectRaw('o.status as label')
            ->selectRaw('COUNT(*) as value')
            ->groupBy('o.status')
            ->orderByDesc('value')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->map(fn ($r) => (int) $r->value)->all(),
        ];
    }

    public function cashVsCreditOrders(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $row = DB::table('orders as o')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->when(! empty($filters['buyer_id']), fn ($q) => $q->where('o.buyer_id', $filters['buyer_id']))
            ->selectRaw("SUM(CASE WHEN o.payment_condition = 'cash' THEN o.total ELSE 0 END) as cash")
            ->selectRaw("SUM(CASE WHEN o.payment_condition = 'credit' THEN o.total ELSE 0 END) as credit")
            ->first();

        return [
            'labels' => ['Contado', 'Crédito'],
            'data' => [
                round((float) ($row->cash ?? 0), 2),
                round((float) ($row->credit ?? 0), 2),
            ],
        ];
    }

    public function shippingMethodMix(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $row = DB::table('orders as o')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->selectRaw("SUM(CASE WHEN o.shipping_method = 'air' THEN 1 ELSE 0 END) as air")
            ->selectRaw("SUM(CASE WHEN o.shipping_method = 'sea' THEN 1 ELSE 0 END) as sea")
            ->first();

        return [
            'labels' => ['Aéreo', 'Marítimo'],
            'data' => [(int) ($row->air ?? 0), (int) ($row->sea ?? 0)],
        ];
    }

    public function topCargoAgencies(array $filters, int $limit = 10): array
    {
        $period = $this->resolvePeriod($filters);
        $rows = DB::table('orders as o')
            ->join('cargo_agencies as ca', 'ca.id', '=', 'o.cargo_agency_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->selectRaw('ca.name as label')
            ->selectRaw('COUNT(*) as value')
            ->groupBy('ca.id', 'ca.name')
            ->orderByDesc('value')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->map(fn ($r) => (int) $r->value)->all(),
        ];
    }

    public function destinationCountryParticipation(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $rows = DB::table('orders as o')
            ->leftJoin('countries as c', 'c.id', '=', 'o.destination_country_id')
            ->where('o.status', '!=', 'cancelled')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->selectRaw("COALESCE(c.name, 'Sin país') as label")
            ->selectRaw('COUNT(*) as value')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('value')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'data' => $rows->map(fn ($r) => (int) $r->value)->all(),
        ];
    }

    /**
     * @return array{labels:list<string>,availability:list<int>,sales:list<int>}
     */
    public function availabilityVsSalesByWeek(array $filters): array
    {
        $period = $this->resolvePeriod($filters);

        $sales = $this->baseDetailsQuery($filters, $period['from'], $period['to'])
            ->selectRaw('fpa.year')
            ->selectRaw('fpa.week_number')
            ->selectRaw('COALESCE(SUM(od.total_stems), 0) as stems')
            ->groupBy('fpa.year', 'fpa.week_number')
            ->get()
            ->keyBy(fn ($r) => $r->year.'-W'.$r->week_number);

        $availabilityQuery = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fpa.active', true);

        if (! empty($filters['farm_id'])) {
            $availabilityQuery->where('fp.farm_id', $filters['farm_id']);
        }
        if (! empty($filters['year'])) {
            $availabilityQuery->where('fpa.year', $filters['year']);
        }

        $availability = $availabilityQuery
            ->selectRaw('fpa.year')
            ->selectRaw('fpa.week_number')
            ->selectRaw('COALESCE(SUM(fpa.available_stems), 0) as stems')
            ->groupBy('fpa.year', 'fpa.week_number')
            ->get()
            ->keyBy(fn ($r) => $r->year.'-W'.$r->week_number);

        $keys = $sales->keys()->merge($availability->keys())->unique()->sort()->values();

        return [
            'labels' => $keys->all(),
            'availability' => $keys->map(fn ($k) => (int) ($availability[$k]->stems ?? 0))->all(),
            'sales' => $keys->map(fn ($k) => (int) ($sales[$k]->stems ?? 0))->all(),
        ];
    }

    /**
     * @return array{accept_hours:?float,dispatch_hours:?float,message:?string}
     */
    public function operationalCycleTimes(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $insufficient = 'Información histórica insuficiente';

        $rows = DB::table('order_farm_fulfillments as off')
            ->join('orders as o', 'o.id', '=', 'off.order_id')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->when(! empty($filters['farm_id']), fn ($q) => $q->where('off.farm_id', $filters['farm_id']))
            ->get([
                'o.created_at',
                'off.accepted_at',
                'off.dispatched_at',
            ]);

        $acceptHours = [];
        $dispatchHours = [];

        foreach ($rows as $row) {
            if ($row->accepted_at) {
                $acceptHours[] = Carbon::parse($row->created_at)->diffInMinutes(Carbon::parse($row->accepted_at)) / 60;
            }
            if ($row->accepted_at && $row->dispatched_at) {
                $dispatchHours[] = Carbon::parse($row->accepted_at)->diffInMinutes(Carbon::parse($row->dispatched_at)) / 60;
            }
        }

        return [
            'accept_hours' => count($acceptHours) ? round(array_sum($acceptHours) / count($acceptHours), 1) : null,
            'dispatch_hours' => count($dispatchHours) ? round(array_sum($dispatchHours) / count($dispatchHours), 1) : null,
            'message' => (count($acceptHours) || count($dispatchHours)) ? null : $insufficient,
        ];
    }

    /**
     * @return list<array{key:string,message:string}>
     */
    public function managerialInsights(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $insights = [];
        $totals = $this->periodTotals($filters, $period['from'], $period['to']);

        if ($totals['stems'] > 0) {
            $topVariety = $this->stemsByVariety($filters, 1);
            if (! empty($topVariety['labels'][0]) && ($topVariety['data'][0] ?? 0) > 0) {
                $share = round(($topVariety['data'][0] / $totals['stems']) * 100, 1);
                $insights[] = [
                    'key' => 'variety_share',
                    'message' => "{$topVariety['labels'][0]} representa {$share}% de los tallos vendidos del período.",
                ];
            }
        }

        $prev = $this->previousPeriod($period['from'], $period['to']);
        $prevTotals = $this->periodTotals($filters, $prev['from'], $prev['to']);
        $growth = $this->pctChange($totals['sales'], $prevTotals['sales']);
        if ($growth !== null) {
            $direction = $growth >= 0 ? 'aumentaron' : 'disminuyeron';
            $insights[] = [
                'key' => 'sales_growth',
                'message' => 'Las ventas '.$direction.' '.abs($growth).'% respecto al período anterior.',
            ];
        }

        $countries = $this->destinationCountryParticipation($filters);
        $countryTotal = array_sum($countries['data']);
        if ($countryTotal > 0 && ! empty($countries['labels'][0])) {
            $share = round(($countries['data'][0] / $countryTotal) * 100, 1);
            $insights[] = [
                'key' => 'country_share',
                'message' => "{$countries['labels'][0]} concentra {$share}% de los pedidos.",
            ];
        }

        $finance = $this->financialTraceability($filters);
        if (($finance['overdue_amount'] ?? 0) > 0) {
            $insights[] = [
                'key' => 'overdue',
                'message' => 'Existe un saldo vencido de $'.number_format((float) $finance['overdue_amount'], 2).'.',
            ];
        }

        $avgExpr = DB::connection()->getDriverName() === 'sqlite'
            ? 'AVG((JULIANDAY(off.accepted_at) - JULIANDAY(o.created_at)) * 24)'
            : 'AVG(TIMESTAMPDIFF(HOUR, o.created_at, off.accepted_at))';

        $slowFarm = DB::table('order_farm_fulfillments as off')
            ->join('farms as f', 'f.id', '=', 'off.farm_id')
            ->join('orders as o', 'o.id', '=', 'off.order_id')
            ->whereNotNull('off.accepted_at')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->selectRaw('f.name')
            ->selectRaw("{$avgExpr} as avg_hours")
            ->groupBy('f.id', 'f.name')
            ->orderByDesc('avg_hours')
            ->first();

        if ($slowFarm && $slowFarm->avg_hours !== null) {
            $insights[] = [
                'key' => 'slow_farm',
                'message' => "La finca {$slowFarm->name} tiene el mayor tiempo promedio de aceptación (".round((float) $slowFarm->avg_hours, 1).' h).',
            ];
        }

        return $insights;
    }

    /**
     * @return array<string, mixed>
     */
    public function decisionIndicators(array $filters): array
    {
        $period = $this->resolvePeriod($filters);
        $prev = $this->previousPeriod($period['from'], $period['to']);
        $current = $this->periodTotals($filters, $period['from'], $period['to']);
        $previous = $this->periodTotals($filters, $prev['from'], $prev['to']);
        $finance = $this->financialTraceability($filters);
        $ops = $this->operationalCycleTimes($filters);

        $fulfillments = DB::table('order_farm_fulfillments as off')
            ->join('orders as o', 'o.id', '=', 'off.order_id')
            ->whereBetween('o.created_at', [$period['from']->toDateTimeString(), $period['to']->toDateTimeString()])
            ->when(! empty($filters['farm_id']), fn ($q) => $q->where('off.farm_id', $filters['farm_id']))
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN off.status = 'accepted' OR off.accepted_at IS NOT NULL THEN 1 ELSE 0 END) as accepted")
            ->selectRaw("SUM(CASE WHEN off.status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->selectRaw("SUM(CASE WHEN off.dispatched_at IS NOT NULL OR off.status IN ('dispatched','completed') THEN 1 ELSE 0 END) as dispatched")
            ->first();

        $totalF = (int) ($fulfillments->total ?? 0);
        $accepted = (int) ($fulfillments->accepted ?? 0);
        $rejected = (int) ($fulfillments->rejected ?? 0);
        $dispatched = (int) ($fulfillments->dispatched ?? 0);

        $insufficient = 'Información histórica insuficiente';

        $avgStemPrice = $current['stems'] > 0 ? round($current['sales'] / $current['stems'], 4) : null;

        return [
            'monthly_sales_growth' => $this->kpiOrInsufficient('Crecimiento mensual ventas %', $this->pctChange($current['sales'], $previous['sales']), $insufficient),
            'annual_sales_growth' => $this->kpiOrInsufficient(
                'Crecimiento anual %',
                $this->pctChange(
                    $this->periodTotals($filters, $period['to']->copy()->startOfYear(), $period['to'])['sales'],
                    $this->periodTotals($filters, $period['to']->copy()->subYear()->startOfYear(), $period['to']->copy()->subYear())['sales']
                ),
                $insufficient
            ),
            'stems_variation' => $this->kpiOrInsufficient('Variación tallos vendidos %', $this->pctChange($current['stems'], $previous['stems']), $insufficient),
            'avg_stem_price' => $avgStemPrice === null
                ? ['label' => 'Precio promedio por tallo', 'value' => null, 'message' => $insufficient]
                : ['label' => 'Precio promedio por tallo', 'value' => $avgStemPrice, 'message' => null],
            'accepted_pct' => $totalF > 0
                ? ['label' => '% pedidos aceptados', 'value' => round(($accepted / $totalF) * 100, 1), 'message' => null]
                : ['label' => '% pedidos aceptados', 'value' => null, 'message' => $insufficient],
            'rejected_pct' => $totalF > 0
                ? ['label' => '% pedidos rechazados', 'value' => round(($rejected / $totalF) * 100, 1), 'message' => null]
                : ['label' => '% pedidos rechazados', 'value' => null, 'message' => $insufficient],
            'dispatch_fulfillment' => $totalF > 0
                ? ['label' => 'Cumplimiento de despacho %', 'value' => round(($dispatched / $totalF) * 100, 1), 'message' => null]
                : ['label' => 'Cumplimiento de despacho %', 'value' => null, 'message' => $insufficient],
            'avg_payment_days' => [
                'label' => 'Días promedio de pago',
                'value' => $finance['avg_payment_days'],
                'message' => $finance['avg_payment_days'] === null ? ($finance['avg_payment_days_message'] ?? $insufficient) : null,
            ],
            'overdue_balance' => [
                'label' => 'Saldo vencido',
                'value' => $finance['overdue_amount'],
                'message' => null,
            ],
            'accept_cycle_hours' => [
                'label' => 'Horas promedio pedido → aceptación',
                'value' => $ops['accept_hours'],
                'message' => $ops['accept_hours'] === null ? $insufficient : null,
            ],
            'dispatch_cycle_hours' => [
                'label' => 'Horas promedio aceptación → despacho',
                'value' => $ops['dispatch_hours'],
                'message' => $ops['dispatch_hours'] === null ? $insufficient : null,
            ],
        ];
    }

    /**
     * @return array{label:string,value:mixed,message:?string}
     */
    private function kpiOrInsufficient(string $label, ?float $value, string $insufficient): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'message' => $value === null ? $insufficient : null,
        ];
    }
}
