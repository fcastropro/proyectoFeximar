@extends('pdf.layout')

@section('content')
<div class="section">
    <h3>Comprador</h3>
    <p><strong>Empresa:</strong> {{ $order->buyer?->company_name ?? '—' }}</p>
    <p><strong>Contacto:</strong> {{ $order->buyer?->contact_name ?? '—' }}</p>
    <p><strong>Email:</strong> {{ $order->buyer?->email ?? '—' }}</p>
    <p><strong>País:</strong> {{ $order->buyer?->country ?? '—' }}</p>
    <p><strong>Ciudad:</strong> {{ $order->buyer?->city ?? '—' }}</p>
</div>

<div class="section">
    <h3>Resumen del pedido</h3>
    <p><strong>Pedido:</strong> #{{ $order->id }}</p>
    <p><strong>Fecha:</strong> {{ $order->created_at?->format('Y-m-d H:i') }}</p>
    <p><strong>Estado:</strong> {{ $order->status }}</p>
    <p><strong>Método de pago:</strong>
        {{ $order->payment_condition === 'credit' ? 'Crédito' : ($order->payment_condition === 'cash' ? 'Contado' : '—') }}
        @if($order->payment_condition === 'credit' && $order->credit_days)
            ({{ $order->credit_days }} días)
        @endif
    </p>
    <p><strong>Total:</strong> ${{ number_format((float) $order->total, 2) }}</p>
    <p><strong>Notas:</strong> {{ $order->notes ?: '—' }}</p>
</div>

<div class="section">
    <h3>Logística</h3>
    <p><strong>Agencia de carga:</strong> {{ $order->cargoAgency?->name ?? '—' }}</p>
    <p><strong>Vía de transporte:</strong> {{ $order->shipping_method === 'air' ? 'Aérea' : ($order->shipping_method === 'sea' ? 'Marítima' : '—') }}</p>
    <p><strong>País destino:</strong> {{ $order->destinationCountry?->name ?? '—' }}</p>
    <p><strong>Destino / ciudad:</strong> {{ $order->destination_city ?: '—' }}</p>
    <p><strong>Aeropuerto:</strong> {{ $order->destination_airport ?: '—' }}</p>
    <p><strong>Puerto:</strong> {{ $order->destination_port ?: '—' }}</p>
    <p><strong>Marcación:</strong> {{ $order->marking ?: '—' }}</p>
</div>

<div class="section">
    <h3>Productos</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Finca</th>
                <th>Producto</th>
                <th>Variedad</th>
                <th>Color</th>
                <th>Long.</th>
                <th>Bunches</th>
                <th>Tallos/bunch</th>
                <th>Tallos</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $d)
                <tr>
                    <td>{{ $d['farm'] ?? '—' }}</td>
                    <td>{{ $d['product'] ?? '—' }}</td>
                    <td>{{ $d['variety'] ?? '—' }}</td>
                    <td>{{ $d['color'] ?? '—' }}</td>
                    <td>{{ $d['length'] ? $d['length'].' cm' : '—' }}</td>
                    <td>{{ $d['bunches'] ?? '—' }}</td>
                    <td>{{ $d['stems_per_bunch'] ?? '—' }}</td>
                    <td>{{ $d['total_stems'] ?? '—' }}</td>
                    <td>
                        @if(isset($d['price_per_stem']) && $d['price_per_stem'] !== null)
                            {{ number_format((float) $d['price_per_stem'], 4) }}
                        @elseif(isset($d['unit_price']))
                            {{ number_format((float) $d['unit_price'], 4) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ number_format((float) ($d['subtotal'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Sin líneas</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="totals">Total del pedido: ${{ number_format((float) $order->total, 2) }}</p>
</div>

<div class="section">
    <h3>Cumplimiento por finca</h3>
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

<div class="section">
    <h3>Pago / liquidaciones</h3>
    <p><strong>Total pedido:</strong> ${{ number_format((float) $order->total, 2) }}</p>
    <p><strong>Pagado (a fincas):</strong> ${{ number_format((float) $paid, 2) }}</p>
    <p><strong>Pendiente estimado:</strong> ${{ number_format((float) $pending, 2) }}</p>
    <p class="note">{{ $financeNote }}</p>
</div>
@endsection
