@extends('pdf.layout')

@section('content')
<div class="section">
    <h3>Datos generales</h3>
    <p><strong>Nombre:</strong> {{ $farm->name }}</p>
    <p><strong>Comercial:</strong> {{ $farm->commercial_name ?: '—' }}</p>
    <p><strong>RUC:</strong> {{ $farm->ruc ?: '—' }}</p>
    <p><strong>Email / Tel:</strong> {{ $farm->email ?: '—' }} / {{ $farm->phone ?: '—' }}</p>
    <p><strong>Ubicación:</strong> {{ $farm->city?->name }}, {{ $farm->province?->name }}, {{ $farm->country?->name }}</p>
    <p><strong>Estado:</strong> {{ $farm->active ? 'Activa' : 'Inactiva' }}</p>
</div>

<div class="section">
    <h3>Productos y presentaciones</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Variedad</th>
                <th>Longitudes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($farm->farmProducts as $fp)
                <tr>
                    <td>{{ $fp->product?->name }}</td>
                    <td>{{ $fp->product?->variety ?: '—' }}</td>
                    <td>
                        {{ $fp->presentations->pluck('stem_length_cm')->filter()->map(fn ($cm) => $cm.' cm')->implode(', ') ?: '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">Sin productos</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h3>Disponibilidad actual</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Variedad</th>
                <th>Long.</th>
                <th>Reportados</th>
                <th>Reservados</th>
                <th>Precio/tallo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($availability as $a)
                <tr>
                    <td>{{ $a->product }}</td>
                    <td>{{ $a->variety ?: '—' }}</td>
                    <td>{{ $a->stem_length_cm }} cm</td>
                    <td>{{ $a->available_stems }}</td>
                    <td>{{ $a->reserved_stems }}</td>
                    <td>{{ number_format((float) $a->price_per_stem, 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sin disponibilidad esta semana</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h3>Histórico de ventas</h3>
    <p class="kpi"><strong>Pedidos:</strong> {{ $sales['orders'] }}</p>
    <p class="kpi"><strong>Monto generado:</strong> ${{ number_format($sales['amount'], 2) }}</p>
    <p class="kpi"><strong>Saldo pendiente (a finca):</strong> ${{ number_format($pending_balance, 2) }}</p>
</div>
@endsection
