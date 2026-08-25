<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desembolsos del día</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .muted { color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f0f0f0; font-size: 10px; }
        .summary td { border: none; padding: 2px 0; }
        .text-right { text-align: right; }
        .check-col { width: 28px; text-align: center; }
    </style>
</head>
<body>
    <h1>Control de desembolsos</h1>
    <div class="muted">Fecha: {{ $dateLabel }} @if($sellerName) | Asesor: {{ $sellerName }} @endif</div>

    <table class="summary">
        <tr><td><strong>Contratos aprobados:</strong></td><td>{{ $summary['approved_count'] }}</td></tr>
        <tr><td><strong>Pendientes de desembolso:</strong></td><td>{{ $summary['pending_count'] }}</td></tr>
        <tr><td><strong>Monto solicitado:</strong></td><td>S/ {{ number_format($summary['total_requested'], 2) }}</td></tr>
        <tr><td><strong>Retanqueo BCP:</strong></td><td>S/ {{ number_format($summary['total_retainage'], 2) }}</td></tr>
        <tr><td><strong>Neto a entregar:</strong></td><td>S/ {{ number_format($summary['total_net'], 2) }}</td></tr>
        <tr><td><strong>Egresos registrados:</strong></td><td>S/ {{ number_format($summary['cash_out_expenses'], 2) }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th class="check-col">OK</th>
                <th>Cliente</th>
                <th>Documento/Grupo</th>
                <th>Asesor</th>
                <th class="text-right">Solicitado</th>
                <th class="text-right">BCP</th>
                <th class="text-right">Neto</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td class="check-col"></td>
                <td>{{ $row['client'] }}</td>
                <td>{{ $row['document'] ?: $row['group_name'] }}</td>
                <td>{{ $row['seller_name'] }}</td>
                <td class="text-right">S/ {{ number_format($row['requested_amount'], 2) }}</td>
                <td class="text-right">S/ {{ number_format($row['bcp_retainage'], 2) }}</td>
                <td class="text-right">S/ {{ number_format($row['net_amount'], 2) }}</td>
                <td>{{ $row['disbursed'] ? 'Desembolsado' : 'Pendiente' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
