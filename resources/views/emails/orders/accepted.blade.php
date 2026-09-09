@extends('emails.layouts.feximar')

@section('content')
    <h1>Tu pedido está en preparación</h1>

    <p>Hola {{ $contactName }},</p>

    <p>
        Tu pedido <strong>#{{ $orderId }}</strong> ha sido aceptado por la finca
        y ya se encuentra en preparación.
    </p>

    <table class="summary" role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <th>Pedido</th>
            <td>#{{ $orderId }}</td>
        </tr>
        <tr>
            <th>Finca</th>
            <td>{{ $farmName }}</td>
        </tr>
        <tr>
            <th>Estado</th>
            <td>En preparación</td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td>{{ $orderDate }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td>USD {{ $total }}</td>
        </tr>
    </table>

    <p class="muted">Puedes ingresar a tu portal FEXIMAR para revisar el estado de tu pedido.</p>

    <div class="btn-wrap">
        <a class="btn" href="{{ $actionUrl }}">Ver mis pedidos</a>
    </div>
@endsection
