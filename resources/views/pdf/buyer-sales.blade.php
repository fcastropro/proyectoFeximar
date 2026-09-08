@extends('pdf.layout')

@section('content')
<table class="data">
    <thead>
        <tr>
            <th>Comprador</th>
            <th>Pedido</th>
            <th>Condición</th>
            <th>Total</th>
            <th>País</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @php $sum = 0; @endphp
        @forelse($rows as $row)
            @php $sum += $row['total']; @endphp
            <tr>
                <td>{{ $row['buyer'] ?? '—' }}</td>
                <td>#{{ $row['order'] }}</td>
                <td>{{ $row['payment'] ?? '—' }}</td>
                <td>${{ number_format($row['total'], 2) }}</td>
                <td>{{ $row['country'] ?? '—' }}</td>
                <td>{{ $row['date'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Sin pedidos</td></tr>
        @endforelse
    </tbody>
</table>
<p class="totals">Total ventas: ${{ number_format($sum, 2) }} · Pedidos: {{ count($rows) }}</p>
@endsection
