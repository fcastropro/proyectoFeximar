<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'FEXIMAR' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f5f2;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2933;
            -webkit-text-size-adjust: 100%;
        }
        .wrapper {
            width: 100%;
            background: #f3f5f2;
            padding: 24px 12px;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5ebe3;
        }
        .header {
            background: linear-gradient(135deg, #14532d 0%, #1b5e20 55%, #2e7d32 100%);
            padding: 28px 24px 22px;
            text-align: center;
        }
        .logo {
            max-height: 64px;
            width: auto;
            display: inline-block;
            margin-bottom: 10px;
        }
        .brand {
            color: #ffffff;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.04em;
            margin: 0;
        }
        .tagline {
            color: #dcedc8;
            font-size: 12px;
            margin: 6px 0 0;
        }
        .body {
            padding: 28px 24px 8px;
            font-size: 15px;
            line-height: 1.55;
            color: #243447;
        }
        .body h1 {
            font-size: 18px;
            margin: 0 0 14px;
            color: #14532d;
        }
        .muted {
            color: #607080;
            font-size: 13px;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 20px;
            background: #f8faf7;
            border: 1px solid #e4ece1;
            border-radius: 8px;
        }
        .summary th,
        .summary td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e8efe5;
            font-size: 13px;
            vertical-align: top;
        }
        .summary th {
            width: 38%;
            color: #3f5b49;
            font-weight: bold;
        }
        .summary tr:last-child th,
        .summary tr:last-child td {
            border-bottom: none;
        }
        .lines {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 18px;
        }
        .lines th,
        .lines td {
            border: 1px solid #dde6d9;
            padding: 8px;
            font-size: 12px;
            text-align: left;
        }
        .lines th {
            background: #e8f5e9;
            color: #1b5e20;
        }
        .btn-wrap {
            text-align: center;
            margin: 24px 0 10px;
        }
        .btn {
            display: inline-block;
            background: #1b5e20;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            padding: 18px 24px 24px;
            text-align: center;
            color: #6b7c6f;
            font-size: 12px;
            border-top: 1px solid #eef3ec;
        }
        .footer strong {
            color: #14532d;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                @if(!empty($logoUrl))
                    <img class="logo" src="{{ $logoUrl }}" alt="FEXIMAR">
                @endif
                <p class="brand">{{ $brand ?? 'FEXIMAR' }}</p>
                <p class="tagline">{{ $tagline ?? 'Premium Flowers From Ecuador' }}</p>
            </div>

            <div class="body">
                @yield('content')
            </div>

            <div class="footer">
                <strong>FEXIMAR</strong><br>
                {{ $tagline ?? 'Premium Flowers From Ecuador' }}<br>
                Sistema Inteligente para Gestión y Análisis de Exportaciones Florícolas
            </div>
        </div>
    </div>
</body>
</html>
