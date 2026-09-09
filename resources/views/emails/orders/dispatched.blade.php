@extends('emails.layouts.feximar')

@section('content')
    <h1>Tu pedido ha sido despachado</h1>

    <p>Hola {{ $contactName }},</p>

    <p>
        Tu pedido ha sido despachado por la finca y continúa con su proceso logístico.
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
            <td>Despachado</td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td>{{ $eventDate }}</td>
        </tr>
        <tr>
            <th>Total de esta finca</th>
            <td>USD {{ $farmTotal }}</td>
        </tr>
        @if(!empty($cargoAgency))
            <tr>
                <th>Agencia de carga</th>
                <td>{{ $cargoAgency }}</td>
            </tr>
        @endif
        @if(!empty($shippingMethod))
            <tr>
                <th>Vía de transporte</th>
                <td>{{ $shippingMethod }}</td>
            </tr>
        @endif
        @if(!empty($destination))
            <tr>
                <th>Destino</th>
                <td>{{ $destination }}</td>
            </tr>
        @endif
        @if(!empty($airportOrPort))
            <tr>
                <th>Aeropuerto / Puerto</th>
                <td>{{ $airportOrPort }}</td>
            </tr>
        @endif
    </table>

    <div class="btn-wrap">
        <a class="btn" href="{{ $actionUrl }}">Ver mis pedidos</a>
    </div>
@endsection
