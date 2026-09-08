@extends('pdf.layout')

@section('content')
<table class="data">
    <thead>
        <tr>
            <th>Finca</th>
            <th>Producto</th>
            <th>Variedad</th>
            <th>Long.</th>
            <th>Reportados</th>
            <th>Reservados</th>
            <th>Efectivo</th>
            <th>Precio/tallo</th>
            <th>Tallos/bunch</th>
            <th>Tipos caja</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row->farm }}</td>
                <td>{{ $row->product }}</td>
                <td>{{ $row->variety }}</td>
                <td>{{ $row->stem_length_cm }} cm</td>
                <td>{{ $row->available_stems }}</td>
                <td>{{ $row->reserved_stems }}</td>
                <td>{{ $row->effective }}</td>
                <td>{{ number_format((float) $row->price_per_stem, 4) }}</td>
                <td>{{ $row->stems_per_bunch ?? '—' }}</td>
                <td>{{ $row->box_types ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="10">Sin oferta para los filtros seleccionados</td></tr>
        @endforelse
    </tbody>
</table>
<p class="totals">Líneas: {{ count($rows) }}</p>
@endsection
