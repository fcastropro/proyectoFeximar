<?php

namespace App\Http\Controllers\Farm;

use App\Models\FarmOrderFinance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends BaseFarmController
{
    public function index(Request $request): Response
    {
        $farm = $this->currentFarm($request);

        $finances = FarmOrderFinance::query()
            ->with(['payments', 'order:id,created_at'])
            ->where('farm_id', $farm->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmOrderFinance $finance) {
                $paid = $finance->paidAmount();
                $balance = $finance->balance();
                $daysElapsed = $finance->created_at
                    ? $finance->created_at->startOfDay()->diffInDays(now()->startOfDay())
                    : 0;
                $daysOverdue = ($finance->due_date && $finance->due_date->isPast() && $balance > 0)
                    ? $finance->due_date->diffInDays(now()->startOfDay())
                    : 0;

                return [
                    'id' => $finance->id,
                    'order_id' => $finance->order_id,
                    'amount' => (float) $finance->amount,
                    'payment_condition' => $finance->payment_condition,
                    'credit_days' => $finance->credit_days,
                    'due_date' => $finance->due_date?->format('Y-m-d'),
                    'status' => $finance->status,
                    'paid_amount' => $paid,
                    'balance' => $balance,
                    'days_elapsed' => $daysElapsed,
                    'days_overdue' => $daysOverdue,
                    'payments' => $finance->payments->map(fn ($payment) => [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'payment_date' => $payment->payment_date?->format('Y-m-d'),
                        'payment_method' => $payment->payment_method,
                        'reference' => $payment->reference,
                        'notes' => $payment->notes,
                    ]),
                ];
            });

        return Inertia::render('Farm/Payments/Index', [
            'finances' => $finances,
            'conditionLabels' => [
                'cash' => 'Contado',
                'credit' => 'Crédito',
            ],
            'statusLabels' => [
                'pending' => 'Pendiente',
                'partial' => 'Parcial',
                'paid' => 'Pagado',
                'overdue' => 'Vencido',
            ],
        ]);
    }
}
