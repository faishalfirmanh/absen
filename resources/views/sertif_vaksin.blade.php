<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>International Certificate of Vaccination - {{ $data->name ?? 'FAJAR RESPATI' }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: Arial, sans-serif;
    font-size: 13px;
    background: #f0f0f0;
    display: flex;
    justify-content: center;
    padding: 30px 20px;
}

/* ── Bootstrap-like col offset ── */
.icv-wrapper {
    width: 600px;
}

/* ── Main ICV container ── */
.icv {
    background: #f5e99a;
    padding: 30px 30px 24px;
    position: relative;
    box-shadow: 0 2px 12px rgba(0,0,0,0.18);
    min-height: 900px;
    display: flex;
    flex-direction: column;
}

/* ── Watermark ── */
.icv .watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 260px;
    height: auto;
    opacity: 0.07;
    pointer-events: none;
    z-index: 0;
}

/* ── All sections above watermark ── */
.icv-header,
.icv-body,
.icv-footer {
    position: relative;
    z-index: 1;
}

/* ════════════════════════
   HEADER
════════════════════════ */
.icv-header {
    text-align: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1.5px solid #b09a1a;
}

/* Baris 1: Pancasila */
.icv-header > div:first-child {
    margin-bottom: 6px;
}

/* Baris 2: WHO + Kemenkes + SatuSehat */
.icv-header > div:nth-child(2) {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.icv-header > div:nth-child(2) .logo-satusehat-group {
    display: flex;
    align-items: center;
    gap: 5px;
}

.icv-header > div:nth-child(2) .logo-satusehat-group span {
    font-size: 15px;
    font-weight: bold;
    color: #111;
    letter-spacing: 0.4px;
}

.icv-header h3 {
    font-size: 15px;
    font-weight: bold;
    color: #222;
    margin-bottom: 3px;
}

.icv-header p {
    font-size: 11.5px;
    font-style: italic;
    color: #555;
}

/* ════════════════════════
   BODY
════════════════════════ */
.icv-body {
    flex: 1;
}

/* Main data: nama + QR */
.main-data {
    border: 1px solid #c8ae30;
    background: #f5e99a;
    margin-bottom: 14px;
}

.main-data table {
    width: 100%;
    border-collapse: collapse;
}

.main-data td {
    padding: 10px 12px;
    vertical-align: middle;
    font-size: 13px;
    color: #222;
    line-height: 1.7;
}

.main-data td:last-child {
    width: 146px;
    text-align: center;
    border-left: 1px solid #c8ae30;
    font-size: 12px;
    font-weight: bold;
    color: #333;
}

.main-data td img {
    display: block;
    margin: 0 auto 4px;
    width: 100px;
    height: 100px;
}

/* Data detail */
.data-detail .title {
    font-weight: bold;
    font-size: 13px;
    color: #222;
    margin-bottom: 2px;
}

.data-detail > p {
    font-size: 11.5px;
    font-style: italic;
    color: #555;
    margin-bottom: 12px;
}

/* ICV Table */
.icv-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}

.icv-table thead tr {
    background: #c8b84a;
}

.icv-table th {
    border: 1px solid #a89820;
    padding: 7px 6px;
    text-align: center;
    vertical-align: middle;
    font-size: 11px;
    color: #222;
    line-height: 1.4;
    font-weight: normal;
}

.icv-table th strong {
    display: block;
    font-weight: bold;
    margin-bottom: 2px;
}

.icv-table tbody tr {
    background: #f5e99a;
}

.icv-table td {
    border: 1px solid #c8ae30;
    padding: 9px 7px;
    text-align: center;
    vertical-align: middle;
    font-size: 11.5px;
    color: #222;
    line-height: 1.5;
}

.icv-table td:first-child {
    text-align: left;
    padding-left: 10px;
}

.text-bold {
    font-weight: bold;
}

/* ════════════════════════
   FOOTER
════════════════════════ */
.icv-footer {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #b09a1a;
    text-align: center;
}

.icv-footer .pagination {
    margin-bottom: 8px;
    font-size: 12px;
    color: #333;
}

.icv-footer .pagination strong {
    font-size: 12.5px;
    color: #222;
}

.icv-footer .pagination p {
    font-size: 11px;
    font-style: italic;
    color: #555;
    margin-top: 3px;
}

.icv-footer .issued p {
    font-size: 12px;
    color: #222;
    line-height: 1.6;
}

@media print {
    body { background: white; padding: 0; }
    .icv { box-shadow: none; }
}
</style>
</head>
<body>
<div class="icv-wrapper">
<div class="icv">

    {{-- Watermark --}}
    <img src="{{ asset('assets/img/logo1.png') }}" class="watermark" alt="">

    {{-- ══════════════════════════════
         HEADER
    ══════════════════════════════ --}}
    <section class="icv-header">

        {{-- Baris 1: Pancasila tengah --}}
        <div>
            <img src="{{ asset('assets/img/pancasila.png') }}"
                 alt="Logo Garuda"
                 height="50"><br>
        </div>

        {{-- Baris 2: WHO kiri | Kemenkes tengah | SatuSehat kanan --}}
        <div>
            <img src="{{ asset('assets/img/pbb.png') }}"
                 alt="Logo WHO"
                 height="30">

            <img src="{{ asset('assets/img/kemenkes_full.png') }}"
                 alt="Logo Kemenkes"
                 height="30">

            <div class="logo-satusehat-group">
                <img src="{{ asset('assets/img/satu_sehat.webp') }}"
                     alt="Logo Satusehat"
                     height="30">
                <span>SATUSEHAT</span>
            </div>
        </div>

        <h3>International Certificate of Vaccination (Prophylaxis)</h3>
        <p>Certificat International de Vaccination ou de Prophylaxie</p>

    </section>

    {{-- ══════════════════════════════
         BODY
    ══════════════════════════════ --}}
    <section class="icv-body">

        {{-- Data utama: nama + QR --}}
        <div class="main-data">
            <table>
                <tbody>
                    <tr>
                        <td>
                            <p class="text-bold">{{ $data->name ?? 'FAJAR RESPATI' }}</p>
                            <p>Passport {{ $data->passport ?? 'X5372153' }}</p>
                            <p>{{ $data->dob ?? '05th September 1994' }}</p>
                        </td>
                        <td>
                            {{-- QR Code --}}
                            <img src="{{ $qrCode ?? '' }}" alt="QR Code">
                            {{ $data->cert_number ?? 'P00-00032538' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Detail vaksinasi --}}
        <div class="data-detail">

            <p class="title">In accordance with the International Health Regulations</p>
            <p>compormement au Reglement sanitaire international</p>

            {{-- Tabel Vaksin Utama --}}
            <table class="icv-table" style="margin-bottom: 20px">
                <thead>
                    <tr>
                        <th>
                            <strong>Vaccine or Prophylaxis</strong>
                            Vaccin ou agent prophylactique
                        </th>
                        <th>
                            <strong>Manufacturer and Batch no. of vaccine or prophylaxis</strong>
                            Fabircant du vaccin ou de l'agent prophylactique et numero du lot
                        </th>
                        <th>
                            <strong>Date</strong>
                            Date
                        </th>
                        <th>
                            <strong>Valid Until</strong>
                            Valiable jusqu'au
                        </th>
                        <th>
                            <strong>Administering Location &amp; Supervising Clinician</strong>
                            Lieu d'administration et Clinicien superviseur
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vaccines ?? [] as $vaccine)
                    <tr>
                        <td class="text-bold">{{ $vaccine->name }}</td>
                        <td>{{ $vaccine->batch }}</td>
                        <td>{{ $vaccine->date }}</td>
                        <td>{{ $vaccine->valid_until }}</td>
                        <td>{{ $vaccine->location }}<br>{{ $vaccine->clinician }}</td>
                    </tr>
                    @empty
                    {{-- Fallback statis jika tidak ada data --}}
                    <tr>
                        <td class="text-bold">MENINGITIS<br><span style="font-weight:normal">MENINGOCOCCUS</span></td>
                        <td>MERSI B20241126-2</td>
                        <td>15th January 2026</td>
                        <td>29th January 2029</td>
                        <td>Klinik Firdaus<br>dr. MARETTA PRISTIANTY</td>
                    </tr>
                    <tr>
                        <td class="text-bold">POLIO</td>
                        <td>BIOFARMA 2101824</td>
                        <td>15th January 2026</td>
                        <td>29th January 2027</td>
                        <td>Klinik Firdaus<br>dr. MARETTA PRISTIANTY</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Tabel Booster / Other Details --}}
            <table class="icv-table">
                <thead>
                    <tr>
                        <th><strong>Disease targeted</strong></th>
                        <th><strong>Date</strong></th>
                        <th><strong>Manufacture and Batch No. of vaccine or prophylaxis</strong></th>
                        <th><strong>Next Booster</strong></th>
                        <th><strong>Official stamp and signature</strong></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($boosters ?? [] as $booster)
                    <tr>
                        <td class="text-bold">{{ $booster->disease }}</td>
                        <td>{{ $booster->date }}</td>
                        <td>{{ $booster->batch }}</td>
                        <td>{{ $booster->next_booster }}</td>
                        <td></td>
                    </tr>
                    @empty
                    <tr>
                        <td class="text-bold"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </section>

    {{-- ══════════════════════════════
         FOOTER
    ══════════════════════════════ --}}
    <section class="icv-footer">
        <div class="pagination">
            <strong>Penafisan (Disclaimer):</strong>
            <p>Nomor kode ICV elektronik (eICV) berbeda dengan nomor seri ICV fisik</p>
            <br>
        </div>
        <div class="issued">
            <p class="text-bold">This certificate was issued by Ministry of Health of Indonesia</p>
            <p>Ce certificat a &eacute;t&eacute; d&eacute;livr&eacute; par le minist&egrave;re Indon&eacute;sien de la Sant&eacute;</p>
        </div>
    </section>

</div>
</div>
</body>
</html>