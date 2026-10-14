<html>
<head>
    <title>Prueba PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .content { padding: 20px; background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Prueba de PDF con Dompdf</h1>
    <div class="content">
        <p>{{ $msg ?? "Mensaje predeterminado" }}</p>
        <p>Generado el: {{ date("d/m/Y H:i:s") }}</p>
    </div>
</body>
</html>
