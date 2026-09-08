@extends('pdf.layout')

@section('content')
<table class="data">
    <thead>
        <tr>
            <th>Finca</th>
            <th>Contacto</th>
            <th>Provincia</th>
            <th>Ciudad</th>
            <th>Productos</th>
            <th>Estado</th>
            <th>Disp. semana (tallos efectivos)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['contact'] ?: '—' }}</td>
                <td>{{ $row['province'] ?? '—' }}</td>
                <td>{{ $row['city'] ?? '—' }}</td>
                <td>{{ $row['products'] ?: '—' }}</td>
                <td>{{ $row['active'] }}</td>
                <td>{{ $row['week_availability'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Sin fincas</td></tr>
        @endforelse
    </tbody>
</table>
<p class="totals">Total fincas: {{ count($rows) }}</p>
@endsection
