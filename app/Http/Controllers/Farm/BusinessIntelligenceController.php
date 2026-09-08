<?php

namespace App\Http\Controllers\Farm;

use App\Services\BusinessIntelligence\BusinessIntelligenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessIntelligenceController extends BaseFarmController
{
    public function __construct(
        private readonly BusinessIntelligenceService $bi,
    ) {}

    public function executive(Request $request): Response
    {
        $farm = $this->currentFarm($request);
        $filters = $this->bi->filtersForFarm($request, $farm->id);
        $summary = $this->bi->farmExecutiveKpis($filters);
        $finance = $this->bi->financialTraceability($filters);

        return Inertia::render('Farm/BusinessIntelligence/Executive', [
            'farm' => ['id' => $farm->id, 'name' => $farm->name],
            'filters' => $this->publicFilters($filters),
            'filterOptions' => $this->bi->filterOptionsForFarm($farm->id),
            'summary' => $summary,
            'finance' => $finance,
            'salesByMonth' => $this->bi->salesByMonth($filters),
            'topBuyers' => $this->bi->topBuyers($filters),
        ]);
    }

    public function sales(Request $request): Response
    {
        $farm = $this->currentFarm($request);
        $filters = $this->bi->filtersForFarm($request, $farm->id);

        return Inertia::render('Farm/BusinessIntelligence/Sales', [
            'farm' => ['id' => $farm->id, 'name' => $farm->name],
            'filters' => $this->publicFilters($filters),
            'filterOptions' => $this->bi->filterOptionsForFarm($farm->id),
            'period' => $this->bi->farmExecutiveKpis($filters)['period'],
            'charts' => [
                'sales_by_month' => $this->bi->salesByMonth($filters),
                'stems_by_variety' => $this->bi->stemsByVariety($filters),
                'sales_by_variety' => $this->bi->salesByVariety($filters),
                'top_buyers' => $this->bi->topBuyers($filters),
                'sales_by_country' => $this->bi->salesByCountry($filters),
                'boxes_by_type' => $this->bi->boxesByType($filters),
                'avg_stem_price' => $this->bi->avgStemPriceByMonth($filters),
                'availability_vs_sold' => $this->bi->availabilityVsSoldByWeek($filters),
            ],
            'historicalTable' => $this->bi->farmHistoricalTable($filters),
            'currentOperations' => $this->bi->farmCurrentOperations($filters),
            'finance' => $this->bi->financialTraceability($filters),
        ]);
    }

    public function trends(Request $request): Response
    {
        $farm = $this->currentFarm($request);
        $filters = $this->bi->filtersForFarm($request, $farm->id);

        return Inertia::render('Farm/BusinessIntelligence/Trends', [
            'farm' => ['id' => $farm->id, 'name' => $farm->name],
            'filters' => $this->publicFilters($filters),
            'filterOptions' => $this->bi->filterOptionsForFarm($farm->id),
            'period' => $this->bi->farmExecutiveKpis($filters)['period'],
            'trends' => $this->bi->farmTrends($filters),
            'finance' => $this->bi->financialTraceability($filters),
            'yearOverYear' => $this->bi->yearOverYear($filters),
            'salesByMonth' => $this->bi->salesByMonth($filters),
        ]);
    }

    public function predictionDataset(Request $request): StreamedResponse
    {
        $farm = $this->currentFarm($request);
        $rows = $this->bi->predictionDataset(null, null, $farm->id);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'period',
                'variety',
                'stem_length_cm',
                'buyer',
                'country',
                'stems_sold',
                'avg_stem_price',
                'orders_count',
                'availability',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->period,
                    $row->variety,
                    $row->stem_length_cm,
                    $row->buyer,
                    $row->country,
                    $row->stems_sold,
                    $row->avg_stem_price,
                    $row->orders_count,
                    $row->availability,
                ]);
            }

            fclose($out);
        }, 'feximar-farm-demand-dataset.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Nunca devolver farm_id manipulable al cliente como filtro editable.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function publicFilters(array $filters): array
    {
        unset($filters['farm_id']);

        return $filters;
    }
}
