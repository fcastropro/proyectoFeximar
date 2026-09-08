<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Reporte FEXIMAR' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 24px; }
        .header { border-bottom: 2px solid #1b5e20; padding-bottom: 10px; margin-bottom: 16px; }
        .header-table { width: 100%; }
        .logo { height: 48px; }
        .brand { font-size: 18px; font-weight: bold; color: #1b5e20; }
        .tagline { font-size: 9px; color: #555; margin-top: 2px; }
        .title { font-size: 15px; font-weight: bold; margin: 12px 0 8px; }
        .meta { font-size: 10px; color: #444; margin-bottom: 12px; }
        .meta span { display: inline-block; margin-right: 14px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; vertical-align: top; }
        table.data th { background: #e8f5e9; font-size: 10px; }
        .totals { margin-top: 10px; font-weight: bold; }
        .section { margin-top: 14px; }
        .section h3 { font-size: 12px; margin: 0 0 6px; color: #1b5e20; border-bottom: 1px solid #c8e6c9; padding-bottom: 3px; }
        .footer { position: fixed; bottom: 16px; left: 24px; right: 24px; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 6px; }
        .note { font-size: 9px; color: #666; margin-top: 8px; }
        .kpi { display: inline-block; margin-right: 18px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td width="70">
                    @if(!empty($logoPath) && is_file($logoPath))
                        <img class="logo" src="{{ $logoPath }}" alt="FEXIMAR">
                    @endif
                </td>
                <td>
                    <div class="brand">FEXIMAR</div>
                    <div class="tagline">{{ $systemTagline ?? '' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="title">{{ $title ?? 'Reporte' }}</div>

    <div class="meta">
        <span>Generado: {{ $generatedAt ?? now()->format('Y-m-d H:i') }}</span>
        <span>Usuario: {{ $generatedBy ?? 'Sistema' }}</span>
        @if(!empty($filtersUsed))
            <span>Filtros:
                @foreach($filtersUsed as $label => $value)
                    {{ $label }}={{ $value }}@if(!$loop->last); @endif
                @endforeach
            </span>
        @endif
    </div>

    @yield('content')

    <div class="footer">
        FEXIMAR · {{ $generatedAt ?? now()->format('Y-m-d H:i') }} · Página <span class="pagenum"></span>
    </div>
</body>
</html>
