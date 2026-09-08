@extends('pdf.layout')

@section('content')
<table class="data">
    <thead>
        <tr>
            <th>Finca</th>
            <th>Pedido</th>
            <th>Total</th>
            <th>Condición</th>
            <th>Vencimiento</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @php $sumTotal = 0; $sumPaid = 0; $sumBal = 0; @endphp
        @forelse($rows as $row)
            @php
                $sumTotal += $row['total'];
                $sumPaid += $row['paid'];
                $sumBal += $row['balance'];
            @endphp
            <tr>
                <td>{{ $row['farm'] }}</td>
                <td>#{{ $row['order'] }}</td>
                <td>${{ number_format($row['total'], 2) }}</td>
                <td>{{ $row['condition'] }}</td>
                <td>{{ $row['due_date'] ?? '—' }}</td>
                <td>${{ number_format($row['paid'], 2) }}</td>
                <td>${{ number_format($row['balance'], 2) }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Sin registros</td></tr>
        @endforelse
    </tbody>
</table>
<p class="totals">
    Total: ${{ number_format($sumTotal, 2) }} ·
    Pagado: ${{ number_format($sumPaid, 2) }} ·
    Saldo: ${{ number_format($sumBal, 2) }}
</p>
@endsection
