<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Menggunakan alias Facade PDF
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DokumentController extends Controller
{
    public function cetakPdf()
    {
        // 1. Siapkan URL tujuan saat QR di-scan
        $urlTujuan = 'https://www.google.com';

        // 2. Generate QR Code ke bentuk SVG, lalu ubah jadi format Base64
        $qrCode = base64_encode(QrCode::format('svg')->size(150)->generate($urlTujuan));

        // 3. Siapkan data yang akan dikirim ke file .blade.php
        $data = [
            'judul' => 'Dokumen Rahasia & Penting',
            'qrCode' => $qrCode,
        ];

        // 4. Load view template blade dan kirim data
        // Pastikan kamu punya file resources/views/pdf/template.blade.php
        $pdf = Pdf::loadView('pdf.template', $data);

        // 5. Tampilkan PDF di browser (gunakan ->download('nama.pdf') jika ingin langsung terunduh)
        return $pdf->stream('dokumen_dengan_qr.pdf');
    }
}