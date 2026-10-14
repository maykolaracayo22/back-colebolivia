<!DOCTYPE html>
<html>
<head>
    <title>Consolidado Financiero {{ $year }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Consolidado Financiero - Año {{ $year }}</h1>

    @foreach($fees as $studentId => $studentFees)
        <h2>{{ $studentFees->first()->student->nombres }} {{ $studentFees->first()->student->apellidos }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Fecha Vencimiento</th>
                    <th>Fecha Pago</th>
                    <th>Monto Pagado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($studentFees as $fee)
                    <tr>
                        <td>{{ $fee->feeConfig->concepto }}</td>
                        <td>{{ number_format($fee->monto, 2) }} Bs</td>
                        <td>{{ ucfirst($fee->estado) }}</td>
                        <td>{{ $fee->fecha_vencimiento ? $fee->fecha_vencimiento->format('d/m/Y') : '-' }}</td>
                        <td>{{ $fee->fecha_pago ? $fee->fecha_pago->format('d/m/Y') : '-' }}</td>
                        <td>{{ $fee->monto_pagado ? number_format($fee->monto_pagado, 2) . ' Bs' : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="5">Total Pendiente</td>
                    <td>{{ number_format($studentFees->where('estado', 'pendiente')->sum('monto'), 2) }} Bs</td>
                </tr>
                <tr class="total">
                    <td colspan="5">Total Pagado</td>
                    <td>{{ number_format($studentFees->where('estado', 'pagado')->sum('monto_pagado'), 2) }} Bs</td>
                </tr>
            </tbody>
        </table>
        <br>
    @endforeach
</body>
</html>
