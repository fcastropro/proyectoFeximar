@extends('pdf.layout')

@section('content')
<div class="section">
    <h3>Resumen</h3>
    <p><strong>Comprador:</strong> {{ $order->buyer?->company_name }} ({{ $order->buyer?->contact_name }} · {{ $order->buyer?->email }})</p>
    <p><strong>Fecha:</strong> {{ $order->created_at?->format('Y-m-d H:i') }}</p>
    <p><strong>Estado:</strong> {{ $order->status }}</p>
    <p><strong>Total:</strong> ${{ number_format((float) $order->total, 2) }}</p>
</div>

<div class="section">
    <h3>Productos</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Finca</th>
                <th>Producto</th>
                <th>Variedad</th>
                <th>Long.</th>
                <th>Bunches</th>
                <th>Tallos/bunch</th>
                <th>Tallos</th>
                <th>Caja</th>
                <th>Cajas</th>
                <th>P/tallo</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $d)
                <tr>
                    <td>{{ $d['farm'] ?? '—' }}</td>
                    <td>{{ $d['product'] ?? '—' }}</td>
                    <td>{{ $d['variety'] ?? '—' }}</td>
                    <td>{{ $d['length'] ? $d['length'].' cm' : '—' }}</td>
                    <td>{{ $d['bunches'] ?? '—' }}</td>
                    <td>{{ $d['stems_per_bunch'] ?? '—' }}</td>
                    <td>{{ $d['total_stems'] ?? '—' }}</td>
                    <td>{{ $d['box_type'] ?? '—' }}</td>
                    <td>{{ $d['boxes'] ?? '—' }}</td>
                    <td>{{ isset($d['price_per_stem']) ? number_format((float) $d['price_per_stem'], 4) : '—' }}</td>
                    <td>{{ number_format((float) ($d['subtotal'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="11">Sin líneas</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h3>Logística</h3>
    <p><strong>Agencia:</strong> {{ $order->cargoAgency?->name ?? '—' }}</p>
    <p><strong>Vía:</strong> {{ $order->shipping_method === 'air' ? 'Aérea' : ($order->shipping_method === 'sea' ? 'Marítima' : '—') }}</p>
    <p><strong>País destino:</strong> {{ $order->destinationCountry?->name ?? '—' }}</p>
    <p><strong>Destino:</strong>
        {{ $order->destination_city }}
        @if($order->destination_airport) · Aeropuerto: {{ $order->destination_airport }} @endif
        @if($order->destination_port) · Puerto: {{ $order->destination_port }} @endif
    </p>
    <p><strong>Marcación:</strong> {{ $order->marking ?: '—' }}</p>
</div>

<div class="section">
    <h3>Pago</h3>
    <p><strong>Condición:</strong> {{ $order->payment_condition === 'credit' ? 'Crédito' : ($order->payment_condition === 'cash' ? 'Contado' : '—') }}</p>
    <p><strong>Días crédito:</strong> {{ $order->credit_days ?? '—' }}</p>
    <p><strong>Total pedido:</strong> ${{ number_format((float) $order->total, 2) }}</p>
    <p><strong>Pagado (a fincas):</strong> ${{ number_format((float) $paid, 2) }}</p>
    <p><strong>Pendiente estimado:</strong> ${{ number_format((float) $pending, 2) }}</p>
    <p class="note">{{ $financeNote }}</p>
</div>

<div class="section">
    <h3>Trazabilidad por finca</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Finca</th>
                <th>Estado</th>
                <th>Recibido</th>
                <th>Aceptado</th>
                <th>Preparación</th>
                <th>Listo</th>
                <th>Despachado</th>
                <th>Completado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->farmFulfillments as $f)
                <tr>
                    <td>{{ $f->farm?->name }}</td>
                    <td>{{ $f->status }}</td>
                    <td>{{ $f->received_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $f->accepted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $f->prepared_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $f->ready_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $f->dispatched_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $f->completed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Sin fulfillments</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
