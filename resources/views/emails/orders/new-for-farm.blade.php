@extends('emails.layouts.feximar')

@section('content')
    <h1>Nuevo pedido recibido</h1>

    <p>Hola {{ $farmGreeting }},</p>

    <p>Has recibido un nuevo pedido a través de FEXIMAR.</p>

    <table class="summary" role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <th>Pedido</th>
            <td>#{{ $orderId }}</td>
        </tr>
        <tr>
            <th>Comprador</th>
            <td>{{ $buyerCompany }}</td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td>{{ $orderDate }}</td>
        </tr>
        <tr>
            <th>Estado</th>
            <td>{{ $orderStatus }}</td>
        </tr>
        <tr>
            <th>Total de tu finca</th>
            <td>USD {{ $farmTotal }}</td>
        </tr>
    </table>

    <p><strong>Detalle de productos de tu finca</strong></p>

    <table class="lines" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Variedad</th>
                <th>Color</th>
                <th>Long.</th>
                <th>Bunches</th>
                <th>Tallos</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ $line['product'] }}</td>
                    <td>{{ $line['variety'] }}</td>
                    <td>{{ $line['color'] }}</td>
                    <td>{{ $line['length'] }}</td>
                    <td>{{ $line['bunches'] }}</td>
                    <td>{{ $line['total_stems'] }}</td>
                    <td>{{ $line['subtotal'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Sin líneas para esta finca.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="btn-wrap">
        <a class="btn" href="{{ $actionUrl }}">Ver pedido</a>
    </div>
@endsection
