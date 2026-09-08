<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessIntelligence\BusinessIntelligenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessIntelligenceController extends Controller
{
    public function __construct(
        private readonly BusinessIntelligenceService $bi,
    ) {}

    public function executive(Request $request): Response
    {
        $filters = $this->bi->filtersFromRequest($request);
        $summary = $this->bi->executiveKpis($filters);

        return Inertia::render('Admin/BusinessIntelligence/Executive', [
            'filters' => $filters,
            'filterOptions' => $this->bi->filterOptions(),
            'summary' => $summary,
            'finance' => $this->bi->financialTraceability($filters),
            'salesByMonth' => $this->bi->salesByMonth($filters),
            'topBuyers' => $this->bi->topBuyers($filters),
            'insights' => $this->bi->managerialInsights($filters),
            'decisionIndicators' => $this->bi->decisionIndicators($filters),
        ]);
    }

    public function sales(Request $request): Response
    {
        $filters = $this->bi->filtersFromRequest($request);
        $availabilityVsSales = $this->bi->availabilityVsSalesByWeek($filters);

        return Inertia::render('Admin/BusinessIntelligence/Sales', [
            'filters' => $filters,
            'filterOptions' => $this->bi->filterOptions(),
            'period' => $this->bi->executiveKpis($filters)['period'],
            'charts' => [
                'sales_by_month' => $this->bi->salesByMonth($filters),
                'sales_by_year' => $this->bi->salesByYear($filters),
                'stems_by_variety' => $this->bi->stemsByVariety($filters),
                'bunches_by_variety' => $this->bi->bunchesByVariety($filters),
                'sales_by_variety' => $this->bi->salesByVariety($filters),
                'top_buyers' => $this->bi->topBuyers($filters),
                'sales_by_country' => $this->bi->salesByCountry($filters),
                'destination_countries' => $this->bi->destinationCountryParticipation($filters),
                'sales_by_farm' => $this->bi->salesByFarm($filters),
                'boxes_by_type' => $this->bi->boxesByType($filters),
                'avg_stem_price' => $this->bi->avgStemPriceByMonth($filters),
                'availability_vs_sales' => [
                    'labels' => $availabilityVsSales['labels'],
                    'datasets' => [
                        ['label' => 'Disponibilidad', 'data' => $availabilityVsSales['availability']],
                        ['label' => 'Vendidos', 'data' => $availabilityVsSales['sales']],
                    ],
                ],
                'shipping_mix' => $this->bi->shippingMethodMix($filters),
                'top_cargo_agencies' => $this->bi->topCargoAgencies($filters),
            ],
            'historicalTable' => $this->bi->historicalVarietyTable($filters),
            'currentAvailability' => $this->bi->currentAvailabilityByVariety($filters),
        ]);
    }

    public function trends(Request $request): Response
    {
        $filters = $this->bi->filtersFromRequest($request);

        return Inertia::render('Admin/BusinessIntelligence/Trends', [
            'filters' => $filters,
            'filterOptions' => $this->bi->filterOptions(),
            'period' => $this->bi->executiveKpis($filters)['period'],
            'trends' => $this->bi->trends($filters),
            'finance' => $this->bi->financialTraceability($filters),
            'salesByMonth' => $this->bi->salesByMonth($filters),
            'insights' => $this->bi->managerialInsights($filters),
            'decisionIndicators' => $this->bi->decisionIndicators($filters),
        ]);
    }

    public function finance(Request $request): Response
    {
        $filters = $this->bi->filtersFromRequest($request);
        $finance = $this->bi->financialTraceability($filters);

        return Inertia::render('Admin/BusinessIntelligence/Finance', [
            'filters' => $filters,
            'filterOptions' => $this->bi->filterOptions(),
            'period' => $this->bi->executiveKpis($filters)['period'],
            'finance' => $finance,
            'charts' => [
                'cash_vs_credit' => $this->bi->cashVsCreditOrders($filters),
                'collected_vs_pending' => $finance['collected_vs_pending'],
            ],
            'decisionIndicators' => $this->bi->decisionIndicators($filters),
        ]);
    }

    public function operations(Request $request): Response
    {
        $filters = $this->bi->filtersFromRequest($request);

        return Inertia::render('Admin/BusinessIntelligence/Operations', [
            'filters' => $filters,
            'filterOptions' => $this->bi->filterOptions(),
            'period' => $this->bi->executiveKpis($filters)['period'],
            'charts' => [
                'orders_by_status' => $this->bi->ordersByStatus($filters),
                'shipping_mix' => $this->bi->shippingMethodMix($filters),
                'top_cargo_agencies' => $this->bi->topCargoAgencies($filters),
            ],
            'cycleTimes' => $this->bi->operationalCycleTimes($filters),
            'decisionIndicators' => $this->bi->decisionIndicators($filters),
            'insights' => $this->bi->managerialInsights($filters),
        ]);
    }

    public function prediction(Request $request): Response
    {
        $filters = $this->bi->filtersFromRequest($request);

        return Inertia::render('Admin/BusinessIntelligence/Prediction', [
            'filters' => $filters,
            'filterOptions' => $this->bi->filterOptions(),
            'message' => 'Módulo preparado para utilizar históricos de ventas para proyección de demanda.',
            'datasetReady' => true,
        ]);
    }

    public function predictionDataset(Request $request): StreamedResponse
    {
        $rows = $this->bi->predictionDataset();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'period_date',
                'period',
                'variety',
                'stem_length_cm',
                'farm',
                'buyer',
                'country',
                'stems_sold',
                'avg_stem_price',
                'orders_count',
                'availability',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->period_date,
                    $row->period,
                    $row->variety,
                    $row->stem_length_cm ?? '',
                    $row->farm,
                    $row->buyer ?? '',
                    $row->country,
                    $row->stems_sold,
                    $row->avg_stem_price,
                    $row->orders_count,
                    $row->availability ?? '',
                ]);
            }

            fclose($out);
        }, 'feximar-demand-dataset.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
