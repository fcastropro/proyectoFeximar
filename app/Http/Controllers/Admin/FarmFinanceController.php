<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmOrderFinance;
use App\Models\FarmPayment;
use App\Services\Admin\ActivityLogger;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FarmFinanceController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $finances = FarmOrderFinance::query()
            ->with(['farm:id,name', 'order:id,created_at', 'payments'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (FarmOrderFinance $finance) => [
                'id' => $finance->id,
                'order_id' => $finance->order_id,
                'farm_name' => $finance->farm?->name,
                'amount' => (float) $finance->amount,
                'payment_condition' => $finance->payment_condition,
                'credit_days' => $finance->credit_days,
                'due_date' => $finance->due_date?->format('Y-m-d'),
                'status' => $finance->status,
                'paid_amount' => $finance->paidAmount(),
                'balance' => $finance->balance(),
            ]);

        return Inertia::render('Admin/FarmFinances/Index', [
            'finances' => $finances,
        ]);
    }

    public function create(Request $request): Response
    {
        $orderId = $request->integer('order_id') ?: null;

        return Inertia::render('Admin/FarmFinances/Create', [
            'orders' => Order::query()->orderByDesc('id')->limit(100)->get(['id', 'total', 'created_at']),
            'selectedOrderId' => $orderId,
            'farmOptions' => $orderId ? $this->farmOptionsForOrder($orderId) : [],
            'conditions' => FarmOrderFinance::CONDITIONS,
        ]);
    }

    public function farmOptions(Order $order): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->farmOptionsForOrder($order->id));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'farm_id' => [
                'required',
                'integer',
                'exists:farms,id',
                Rule::unique('farm_order_finances')->where(
                    fn ($q) => $q->where('order_id', $request->integer('order_id'))
                ),
            ],
            'payment_condition' => ['required', 'string', Rule::in(FarmOrderFinance::CONDITIONS)],
            'credit_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $amount = $this->amountForFarm($validated['order_id'], $validated['farm_id']);

        if ($amount <= 0) {
            return back()->withErrors([
                'farm_id' => 'No hay líneas de pedido para esa finca en el pedido seleccionado.',
            ]);
        }

        $dueDate = null;
        if ($validated['payment_condition'] === 'credit') {
            $days = (int) ($validated['credit_days'] ?? 0);
            if ($days < 1) {
                return back()->withErrors([
                    'credit_days' => 'Indica los días de crédito.',
                ]);
            }
            $dueDate = Carbon::now()->addDays($days)->toDateString();
        }

        FarmOrderFinance::create([
            'order_id' => $validated['order_id'],
            'farm_id' => $validated['farm_id'],
            'amount' => $amount,
            'payment_condition' => $validated['payment_condition'],
            'credit_days' => $validated['payment_condition'] === 'credit' ? $validated['credit_days'] : null,
            'due_date' => $dueDate,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('admin.farm-finances.index')
            ->with('success', 'Condición financiera registrada.');
    }

    public function storePayment(Request $request, FarmOrderFinance $farmFinance): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        FarmPayment::create([
            'farm_order_finance_id' => $farmFinance->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $farmFinance->refreshStatus();

        $this->activityLogger->log(
            'payment_registered',
            "Pago de {$validated['amount']} registrado para liquidación #{$farmFinance->id} (pedido #{$farmFinance->order_id})",
            FarmOrderFinance::class,
            $farmFinance->id,
        );

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function show(FarmOrderFinance $farmFinance): Response
    {
        $farmFinance->load(['farm:id,name', 'order:id,created_at', 'payments']);

        return Inertia::render('Admin/FarmFinances/Show', [
            'finance' => [
                'id' => $farmFinance->id,
                'order_id' => $farmFinance->order_id,
                'farm_name' => $farmFinance->farm?->name,
                'amount' => (float) $farmFinance->amount,
                'payment_condition' => $farmFinance->payment_condition,
                'credit_days' => $farmFinance->credit_days,
                'due_date' => $farmFinance->due_date?->format('Y-m-d'),
                'status' => $farmFinance->status,
                'paid_amount' => $farmFinance->paidAmount(),
                'balance' => $farmFinance->balance(),
                'payments' => $farmFinance->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'payment_date' => $p->payment_date?->format('Y-m-d'),
                    'payment_method' => $p->payment_method,
                    'reference' => $p->reference,
                    'notes' => $p->notes,
                ]),
            ],
        ]);
    }

    /**
     * @return array<int, array{farm_id:int,farm_name:string,amount:float}>
     */
    private function farmOptionsForOrder(int $orderId): array
    {
        $fulfillments = OrderFarmFulfillment::query()
            ->with('farm:id,name')
            ->where('order_id', $orderId)
            ->get();

        return $fulfillments->map(fn (OrderFarmFulfillment $item) => [
            'farm_id' => $item->farm_id,
            'farm_name' => $item->farm?->name,
            'amount' => $this->amountForFarm($orderId, $item->farm_id),
        ])->values()->all();
    }

    private function amountForFarm(int $orderId, int $farmId): float
    {
        return round((float) OrderDetail::query()
            ->where('order_id', $orderId)
            ->whereHas('availability.presentation.farmProduct', fn ($q) => $q->where('farm_id', $farmId))
            ->sum('subtotal'), 2);
    }
}
