<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consolidado financiero {{ $year }}</title>
</head>
<body>
    <h1>Consolidado financiero - Año {{ $year }}</h1>

    <p>Total cuentas: {{ $cuentas->count() }}</p>

    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Total deuda</th>
                <th>Total pagado</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($cuentas as $cuenta)
            <tr>
                <td>{{ $cuenta->student->nombres }} {{ $cuenta->student->apellidos }}</td>
                <td>{{ $cuenta->total_debt }}</td>
                <td>{{ $cuenta->total_paid }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
