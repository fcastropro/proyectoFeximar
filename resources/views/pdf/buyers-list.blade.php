@extends('pdf.layout')

@section('content')
<table class="data">
    <thead>
        <tr>
            <th>Empresa</th>
            <th>País</th>
            <th>Contacto</th>
            <th>Email</th>
            <th>Crédito</th>
            <th>Días</th>
            <th>Pedidos</th>
            <th>Compras</th>
            <th>Saldo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['company'] }}</td>
                <td>{{ $row['country'] ?? '—' }}</td>
                <td>{{ $row['contact'] ?? '—' }}</td>
                <td>{{ $row['email'] ?? '—' }}</td>
                <td>{{ $row['credit'] }}</td>
                <td>{{ $row['credit_days'] ?? '—' }}</td>
                <td>{{ $row['orders'] }}</td>
                <td>${{ number_format($row['purchases'], 2) }}</td>
                <td>{{ $row['pending'] }}</td>
            </tr>
        @empty
            <tr><td colspan="9">Sin compradores</td></tr>
        @endforelse
    </tbody>
</table>
@if(!empty($note))
    <p class="note">{{ $note }}</p>
@endif
@endsection
