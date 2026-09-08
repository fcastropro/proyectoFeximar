<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Order;
use App\Services\Admin\AdminReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly AdminReportService $reports,
    ) {}

    public function order(Order $order): Response
    {
        return $this->reports->orderPdf($order);
    }

    public function farms(): Response
    {
        return $this->reports->farmsListPdf();
    }

    public function farm(Farm $farm): Response
    {
        return $this->reports->farmShowPdf($farm);
    }

    public function weeklyAvailability(Request $request): Response
    {
        return $this->reports->weeklyAvailabilityPdf(
            $request->filled('year') ? $request->integer('year') : null,
            $request->filled('week') ? $request->integer('week') : null,
            $request->filled('farm_id') ? $request->integer('farm_id') : null,
            $request->filled('variety_id') ? $request->integer('variety_id') : null,
        );
    }

    public function buyers(): Response
    {
        return $this->reports->buyersListPdf();
    }

    public function farmPayables(): Response
    {
        return $this->reports->farmPayablesPdf();
    }

    public function buyerSales(): Response
    {
        return $this->reports->buyerSalesPdf();
    }
}
