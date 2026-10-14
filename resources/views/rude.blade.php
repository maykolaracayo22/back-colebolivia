<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RUDE - {{ $preinscription->codigo_preinscripcion }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Registro Único de Estudiantes (RUDE)</h1>
    <h2>Código de Preinscripción: {{ $preinscription->codigo_preinscripcion }}</h2>

    <table>
        <tr><th colspan="2">Datos del Estudiante</th></tr>
        <tr><td>Nombres:</td><td>{{ $preinscription->nombres }}</td></tr>
        <tr><td>Apellidos:</td><td>{{ $preinscription->apellidos }}</td></tr>
        <tr><td>Sexo:</td><td>{{ $preinscription->sexo }}</td></tr>
        <tr><td>Fecha de Nacimiento:</td><td>{{ $preinscription->fecha_nac->format('d/m/Y') }}</td></tr>
        <tr><td>CI:</td><td>{{ $preinscription->ci ?? 'N/A' }}</td></tr>
        <tr><td>Nacionalidad:</td><td>{{ $preinscription->nacionalidad ?? 'N/A' }}</td></tr>
        <tr><td>Departamento de Nacimiento:</td><td>{{ $preinscription->dep_nac ?? 'N/A' }}</td></tr>
        <tr><td>Municipio de Nacimiento:</td><td>{{ $preinscription->muni_nac ?? 'N/A' }}</td></tr>
        <tr><td>Dirección:</td><td>{{ $preinscription->direccion ?? 'N/A' }}</td></tr>
        <tr><td>Teléfono:</td><td>{{ $preinscription->telefono ?? 'N/A' }}</td></tr>
        <tr><td>Email:</td><td>{{ $preinscription->email ?? 'N/A' }}</td></tr>
        <tr><td>Pueblo Indígena:</td><td>{{ $preinscription->pueblo_indigena ?? 'N/A' }}</td></tr>
        <tr><td>Discapacidad:</td><td>{{ $preinscription->discapacidad ? 'Sí' : 'No' }}</td></tr>
        <tr><td>Idiomas:</td><td>{{ is_array($preinscription->idiomas) ? implode(', ', $preinscription->idiomas) : 'N/A' }}</td></tr>
        <tr><td>Vulnerabilidad:</td><td>{{ $preinscription->vulnerabilidad ? 'Sí' : 'No' }}</td></tr>
    </table>

    <table>
        <tr><th colspan="2">Datos del Tutor</th></tr>
        <tr><td>Nombre del Tutor:</td><td>{{ $preinscription->tutor_nombre }}</td></tr>
        <tr><td>CI del Tutor:</td><td>{{ $preinscription->tutor_ci ?? 'N/A' }}</td></tr>
        <tr><td>Parentesco:</td><td>{{ $preinscription->tutor_parentesco }}</td></tr>
        <tr><td>Teléfono del Tutor:</td><td>{{ $preinscription->tutor_telefono ?? 'N/A' }}</td></tr>
    </table>

    <table>
        <tr><th colspan="2">Información Académica</th></tr>
        <tr><td>Nivel:</td><td>{{ ucfirst($preinscription->nivel) }}</td></tr>
        <tr><td>Grado:</td><td>{{ $preinscription->grado }}</td></tr>
    </table>

    <p>Estado: {{ ucfirst($preinscription->estado) }}</p>
    <p>Fecha de Preinscripción: {{ $preinscription->created_at->format('d/m/Y H:i') }}</p>
</body>
</html>
