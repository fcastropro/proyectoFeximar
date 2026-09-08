<?php

namespace App\Services\Admin;

use App\Models\Buyer;
use App\Models\Farm;
use App\Models\FarmOrderFinance;
use App\Models\FarmProductAvailability;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AdminReportService
{
    public function logoPath(): string
    {
        $path = public_path('home/images/flores/logo-oscuro.png');

        return is_file($path) ? $path : public_path('favicon.ico');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename): SymfonyResponse
    {
        $pdf = Pdf::loadView($view, array_merge($data, [
            'logoPath' => $this->logoPath(),
            'generatedAt' => Carbon::now()->format('Y-m-d H:i'),
            'generatedBy' => auth()->user()?->name ?? 'Sistema',
            'systemTagline' => 'Sistema Inteligente para Gestión y Análisis de Exportaciones Florícolas',
        ]))->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function orderPdf(Order $order): SymfonyResponse
    {
        $order->load([
            'buyer:id,company_name,contact_name,email',
            'cargoAgency:id,name',
            'destinationCountry:id,name',
            'details.boxType:id,code,name',
            'details.availability.presentation.farmProduct.farm:id,name',
            'details.availability.presentation.farmProduct.product:id,name,variety,variety_id',
            'details.availability.presentation.farmProduct.product.variety:id,name',
            'details.availability.presentation:id,stem_length_cm,stems_per_bunch',
            'farmFulfillments.farm:id,name',
            'farmFinances.payments',
        ]);

        $paid = (float) $order->farmFinances->sum(fn (FarmOrderFinance $f) => $f->paidAmount());
        $financeTotal = (float) $order->farmFinances->sum('amount');

        $details = $order->details->map(function ($detail) {
            $presentation = $detail->availability?->presentation;
            $farmProduct = $presentation?->farmProduct;
            $product = $farmProduct?->product;
            $varietyName = null;
            if ($product) {
                $related = $product->relationLoaded('variety') ? $product->getRelation('variety') : null;
                $varietyName = $related?->name ?? ($product->getAttributes()['variety'] ?? null);
            }

            return [
                'farm' => $farmProduct?->farm?->name,
                'product' => $product?->name,
                'variety' => $varietyName,
                'length' => $presentation?->stem_length_cm,
                'bunches' => $detail->bunches,
                'stems_per_bunch' => $detail->stems_per_bunch,
                'total_stems' => $detail->total_stems,
                'box_type' => $detail->boxType?->code ?? $detail->boxType?->name,
                'boxes' => $detail->boxes,
                'price_per_stem' => $detail->price_per_stem,
                'subtotal' => $detail->subtotal,
            ];
        });

        return $this->download('pdf.order', [
            'title' => 'PEDIDO #'.$order->id,
            'order' => $order,
            'details' => $details,
            'paid' => $paid,
            'pending' => round(max(0, (float) $order->total - $paid), 2),
            'financeNote' => $financeTotal > 0
                ? 'Pagos registrados corresponden a liquidación FEXIMAR → finca.'
                : 'Sin liquidaciones a finca registradas para este pedido.',
            'filtersUsed' => ['Pedido' => '#'.$order->id],
        ], 'feximar-pedido-'.$order->id.'.pdf');
    }

    public function farmsListPdf(): SymfonyResponse
    {
        $now = Carbon::now();
        $year = (int) $now->isoWeekYear;
        $week = (int) $now->isoWeek;

        $farms = Farm::query()
            ->with(['province:id,name', 'city:id,name', 'farmProducts.product:id,name'])
            ->orderBy('name')
            ->get();

        $availabilityByFarm = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fpa.year', $year)
            ->where('fpa.week_number', $week)
            ->where('fpa.active', true)
            ->selectRaw('fp.farm_id, COALESCE(SUM(fpa.available_stems - fpa.reserved_stems), 0) as effective')
            ->groupBy('fp.farm_id')
            ->pluck('effective', 'farm_id');

        $rows = $farms->map(fn (Farm $farm) => [
            'name' => $farm->name,
            'contact' => trim(($farm->email ?? '').' / '.($farm->phone ?? ''), ' /'),
            'province' => $farm->province?->name,
            'city' => $farm->city?->name,
            'products' => $farm->farmProducts->pluck('product.name')->filter()->unique()->implode(', '),
            'active' => $farm->active ? 'Activa' : 'Inactiva',
            'week_availability' => (int) ($availabilityByFarm[$farm->id] ?? 0),
        ]);

        return $this->download('pdf.farms-list', [
            'title' => 'REPORTE DE FINCAS',
            'rows' => $rows,
            'filtersUsed' => [
                'Semana actual' => "{$year}-W{$week}",
            ],
        ], 'feximar-fincas.pdf');
    }

    public function farmShowPdf(Farm $farm): SymfonyResponse
    {
        $farm->load([
            'province:id,name',
            'city:id,name',
            'country:id,name',
            'farmProducts.product:id,name,variety',
            'farmProducts.presentations' => fn ($q) => $q->with('boxConfigs.boxType:id,code,name'),
        ]);

        $now = Carbon::now();
        $year = (int) $now->isoWeekYear;
        $week = (int) $now->isoWeek;

        $availability = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->where('fp.farm_id', $farm->id)
            ->where('fpa.year', $year)
            ->where('fpa.week_number', $week)
            ->select([
                'p.name as product',
                'p.variety',
                'fpp.stem_length_cm',
                'fpa.available_stems',
                'fpa.reserved_stems',
                'fpa.price_per_stem',
            ])
            ->get();

        $sales = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('farm_product_availabilities as fpa', 'fpa.id', '=', 'od.farm_product_availability_id')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->where('fp.farm_id', $farm->id)
            ->where('o.status', '!=', 'cancelled')
            ->selectRaw('COUNT(DISTINCT o.id) as orders')
            ->selectRaw('COALESCE(SUM(od.subtotal), 0) as sales')
            ->first();

        $balance = DB::table('farm_order_finances as fof')
            ->leftJoin('farm_payments as fp', 'fp.farm_order_finance_id', '=', 'fof.id')
            ->where('fof.farm_id', $farm->id)
            ->selectRaw('COALESCE(SUM(fof.amount), 0) as total')
            ->selectRaw('COALESCE(SUM(fp.amount), 0) as paid')
            ->first();

        return $this->download('pdf.farm-show', [
            'title' => 'FICHA DE FINCA',
            'farm' => $farm,
            'availability' => $availability,
            'sales' => [
                'orders' => (int) ($sales->orders ?? 0),
                'amount' => round((float) ($sales->sales ?? 0), 2),
            ],
            'pending_balance' => round(max(0, (float) ($balance->total ?? 0) - (float) ($balance->paid ?? 0)), 2),
            'filtersUsed' => [
                'Finca' => $farm->name,
                'Semana' => "{$year}-W{$week}",
            ],
        ], 'feximar-finca-'.$farm->id.'.pdf');
    }

    public function weeklyAvailabilityPdf(?int $year, ?int $week, ?int $farmId, ?int $varietyId): SymfonyResponse
    {
        $now = Carbon::now();
        $year ??= (int) $now->isoWeekYear;
        $week ??= (int) $now->isoWeek;

        $rows = $this->weeklyAvailabilitySqlite($year, $week, $farmId, $varietyId);

        return $this->download('pdf.weekly-availability', [
            'title' => 'OFERTA SEMANAL FEXIMAR',
            'rows' => $rows,
            'filtersUsed' => array_filter([
                'Año' => $year,
                'Semana' => $week,
                'Finca ID' => $farmId,
                'Variedad ID' => $varietyId,
            ], fn ($v) => $v !== null && $v !== ''),
        ], "feximar-oferta-{$year}-W{$week}.pdf");
    }

    /**
     * @return Collection<int, object>
     */
    private function weeklyAvailabilitySqlite(int $year, int $week, ?int $farmId, ?int $varietyId): Collection
    {
        $query = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('farms as f', 'f.id', '=', 'fp.farm_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->leftJoin('varieties as v', 'v.id', '=', 'p.variety_id')
            ->where('fpa.year', $year)
            ->where('fpa.week_number', $week)
            ->where('fpa.active', true);

        if ($farmId) {
            $query->where('f.id', $farmId);
        }
        if ($varietyId) {
            $query->where('p.variety_id', $varietyId);
        }

        $base = $query
            ->select([
                'fpa.id',
                'f.name as farm',
                'p.name as product',
                DB::raw("COALESCE(NULLIF(v.name, ''), NULLIF(p.variety, ''), p.name) as variety"),
                'fpp.stem_length_cm',
                'fpa.available_stems',
                'fpa.reserved_stems',
                DB::raw('(fpa.available_stems - fpa.reserved_stems) as effective'),
                'fpa.price_per_stem',
                'fpp.stems_per_bunch',
                'fpp.id as presentation_id',
            ])
            ->orderBy('f.name')
            ->get();

        $boxMap = DB::table('presentation_box_configs as pbc')
            ->leftJoin('box_types as bt', 'bt.id', '=', 'pbc.box_type_id')
            ->whereIn('pbc.farm_product_presentation_id', $base->pluck('presentation_id')->unique()->filter())
            ->get(['pbc.farm_product_presentation_id', 'bt.code'])
            ->groupBy('farm_product_presentation_id');

        return $base->map(function ($row) use ($boxMap) {
            $codes = ($boxMap[$row->presentation_id] ?? collect())->pluck('code')->filter()->unique()->implode(',');
            $row->box_types = $codes ?: null;

            return $row;
        });
    }

    public function buyersListPdf(): SymfonyResponse
    {
        $buyers = Buyer::query()
            ->with('country:id,name')
            ->orderBy('company_name')
            ->get();

        $stats = DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->selectRaw('buyer_id, COUNT(*) as orders, COALESCE(SUM(total), 0) as purchases')
            ->groupBy('buyer_id')
            ->get()
            ->keyBy('buyer_id');

        $rows = $buyers->map(fn (Buyer $buyer) => [
            'company' => $buyer->company_name,
            'country' => $buyer->country?->name ?? $buyer->country,
            'contact' => $buyer->contact_name,
            'email' => $buyer->email,
            'credit' => $buyer->credit_allowed ? 'Sí' : 'No',
            'credit_days' => $buyer->credit_days_default,
            'orders' => (int) ($stats[$buyer->id]->orders ?? 0),
            'purchases' => round((float) ($stats[$buyer->id]->purchases ?? 0), 2),
            'pending' => 'N/D*',
        ]);

        return $this->download('pdf.buyers-list', [
            'title' => 'REPORTE DE COMPRADORES',
            'rows' => $rows,
            'note' => '*Saldo pendiente del comprador internacional no tiene tabla de cobros en el MVP; se reportan compras acumuladas.',
            'filtersUsed' => ['Alcance' => 'Todos los compradores'],
        ], 'feximar-compradores.pdf');
    }

    public function farmPayablesPdf(): SymfonyResponse
    {
        $rows = DB::table('farm_order_finances as fof')
            ->join('farms as f', 'f.id', '=', 'fof.farm_id')
            ->leftJoin('farm_payments as fp', 'fp.farm_order_finance_id', '=', 'fof.id')
            ->selectRaw('f.name as farm, fof.order_id, fof.amount, fof.payment_condition, fof.due_date, fof.status')
            ->selectRaw('COALESCE(SUM(fp.amount), 0) as paid')
            ->groupBy('fof.id', 'f.name', 'fof.order_id', 'fof.amount', 'fof.payment_condition', 'fof.due_date', 'fof.status')
            ->orderByDesc('fof.id')
            ->get()
            ->map(fn ($r) => [
                'farm' => $r->farm,
                'order' => $r->order_id,
                'total' => round((float) $r->amount, 2),
                'condition' => $r->payment_condition,
                'due_date' => $r->due_date,
                'paid' => round((float) $r->paid, 2),
                'balance' => round(max(0, (float) $r->amount - (float) $r->paid), 2),
                'status' => $r->status,
            ]);

        return $this->download('pdf.farm-payables', [
            'title' => 'CUENTAS POR PAGAR A FINCAS',
            'rows' => $rows,
            'filtersUsed' => ['Alcance' => 'Todas las liquidaciones'],
        ], 'feximar-cuentas-por-pagar-fincas.pdf');
    }

    public function buyerSalesPdf(): SymfonyResponse
    {
        $rows = Order::query()
            ->with(['buyer:id,company_name', 'destinationCountry:id,name'])
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $o) => [
                'buyer' => $o->buyer?->company_name,
                'order' => $o->id,
                'payment' => $o->payment_condition,
                'total' => (float) $o->total,
                'country' => $o->destinationCountry?->name,
                'date' => $o->created_at?->format('Y-m-d'),
            ]);

        return $this->download('pdf.buyer-sales', [
            'title' => 'VENTAS / PEDIDOS DE COMPRADORES',
            'rows' => $rows,
            'filtersUsed' => ['Alcance' => 'Pedidos no cancelados'],
        ], 'feximar-ventas-compradores.pdf');
    }
}
