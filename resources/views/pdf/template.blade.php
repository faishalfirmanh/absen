<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }
        .kotak-qr {
            margin-top: 30px;
            padding: 20px;
            border: 1px dashed #000;
            display: inline-block;
        }
    </style>
</head>
<body>

    <h1>{{ $judul }}</h1>
    
    <p>Ini adalah contoh dokumen PDF yang di-generate dari Laravel 8.</p>
    <p>Silakan scan QR Code di bawah ini menggunakan kamera HP untuk membuka URL:</p>

    <div class="kotak-qr">
        <img src="data:image/svg+xml;base64, {!! $qrCode !!}" alt="QR Code URL">
    </div>

</body>
</html>