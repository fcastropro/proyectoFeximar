@extends('emails.layouts.feximar')

@section('content')
    <h1>Tu pedido está listo</h1>

    <p>Hola {{ $contactName }},</p>

    <p>
        Tu pedido ha finalizado su preparación y se encuentra listo para continuar con el proceso de despacho.
    </p>

    <p class="muted">
        Esta actualización corresponde a la finca <strong>{{ $farmName }}</strong>
        del pedido <strong>#{{ $orderId }}</strong>.
        Si el pedido incluye otras fincas, cada una se notificará por separado.
    </p>

    <table class="summary" role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <th>Comprador</th>
            <td>{{ $buyerCompany }}</td>
        </tr>
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
            <td>Listo</td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td>{{ $eventDate }}</td>
        </tr>
        <tr>
            <th>Total de esta finca</th>
            <td>USD {{ $farmTotal }}</td>
        </tr>
    </table>

    <div class="btn-wrap">
        <a class="btn" href="{{ $actionUrl }}">Ver mis pedidos</a>
    </div>
@endsection
