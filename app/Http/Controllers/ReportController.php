<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\PengajuanIzin;
use App\Models\Libur;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{

    public function getActiveDays($month_input)
    {
        $month = $month_input;
        $year = Carbon::now()->year;

        // Ambil hari pertama di bulan tersebut untuk mengetahui total hari
        $startDate = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $startDate->daysInMonth;

        // 2. Ambil Semua Data Hari Libur di Bulan Tersebut
        // Format pluck: ['2026-04-10', '2026-04-11', dst]
        $holidays = Libur::whereYear('date_holiday', $year)
            ->whereMonth('date_holiday', $month)
            ->pluck('date_holiday')
            ->toArray();
        $aa = 0;
        // 3. Looping dari Tanggal 1 sampai Akhir Bulan
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = Carbon::create($year, $month, $d);
            $dateString = $currentDate->format('Y-m-d');

            // Skip jika hari MINGGU (Hanya masuk Senin - Sabtu)
            if ($currentDate->isSunday()) {
                continue;
            }

            // Skip jika tanggal ini ada di tabel LIBUR
            if (in_array($dateString, $holidays)) {
                continue;
            }
            $aa++;
        }

        return $aa;

    }
    public function monthlyReport(Request $request)
    {
        // 1. Tentukan periode report (Misal: per bulan dan tahun)
        // Default menggunakan bulan & tahun saat ini jika tidak ada filter
        $month = $request->month ?? date('m');
        $year = $request->year ?? date('Y');



        // 2. AMBIL DATA MASTER USER
        // Asumsi modelnya bernama User dan tabelnya users
        $users = User::orderBy('fullname', 'asc')->get();

        // 3. AMBIL & HITUNG DATA ABSENSI (Hanya Check In)
        // Menggunakan selectRaw agar DB langsung menghitung total hadir per user
        $attendances = Attendance::whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->where('attendance_type', 'check_in')
            ->selectRaw('employee_id, COUNT(*) as total_hadir')
            ->groupBy('employee_id')
            ->pluck('total_hadir', 'employee_id');
        // Hasil: [60 => 22, 61 => 20] (Format array: [employee_id => total_hadir])

        // 4. AMBIL DATA PENGAJUAN IZIN (Hanya yang Approved)
        $izins = PengajuanIzin::where('status', 'Approved')
            ->where(function ($query) use ($month, $year) {
                $query->whereMonth('tgl_mulai', $month)->whereYear('tgl_mulai', $year)
                    ->orWhereMonth('tgl_selesai', $month)->whereYear('tgl_selesai', $year);
            })->get();

        // 5. MAPPING HARI IZIN, SAKIT, & CUTI
        // Karena izin bisa lebih dari 1 hari (tgl_mulai s/d tgl_selesai), kita harus memecahnya
        $izinMap = [];
        foreach ($izins as $izin) {
            // Catatan: Gunakan $izin->user_id atau $izin->employe_id sesuai nama kolom aslimu
            $uid = $izin->employe_id ?? $izin->user_id;

            $start = Carbon::parse($izin->tgl_mulai);
            $end = Carbon::parse($izin->tgl_selesai);

            for ($date = $start; $date->lte($end); $date->addDay()) {
                // Pastikan hanya menghitung hari yang jatuh pada bulan report ini
                if ($date->format('m') == $month && $date->format('Y') == $year) {

                    if (!isset($izinMap[$uid])) {
                        $izinMap[$uid] = ['sakit' => 0, 'izin' => 0, 'cuti' => 0];
                    }

                    if ($izin->jenis == 'Izin Sakit') {
                        $izinMap[$uid]['sakit']++;
                    } elseif ($izin->jenis == 'Izin Keperluan') {
                        $izinMap[$uid]['izin']++;
                    } elseif ($izin->jenis == 'Cuti') {
                        $izinMap[$uid]['cuti']++;
                    }
                }
            }
        }

        // 6. RAKIT DATA FINAL UNTUK REPORT
        $reportData = [];

        foreach ($users as $user) {
            $uid = $user->id;

            // Ambil data hadir (jika tidak ada di array, beri nilai 0)
            $hadir = $attendances->get($uid) ?? 0;

            // Ambil data izin/sakit/cuti
            $sakit = $izinMap[$uid]['sakit'] ?? 0;
            $izin = $izinMap[$uid]['izin'] ?? 0;
            $cuti = $izinMap[$uid]['cuti'] ?? 0;

            // Kalkulasi sesuai rumus yang kamu minta
            $total_tidak_masuk = $sakit + $izin + $cuti;
            $total_masuk = $hadir - $total_tidak_masuk;

            $reportData[] = [
                'id_user' => $user->id,
                'nama_karyawan' => $user->fullname,
                'hadir_mesin' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'cuti' => $cuti,
                'total_masuk_kurangi_izin' => $total_masuk,
                'total_tidak_masuk' => $total_tidak_masuk,
                'hari_efektif' => self::getActiveDays($month)
            ];
        }

        // 7. RETURN RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'periode' => "$year-$month",
            'data' => $reportData
        ], 200);
    }



    public function getActiveDaysInYear($year_input)
    {
        $year = $year_input;

        // 1. Tentukan tanggal awal dan akhir tahun
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        // 2. Ambil Semua Data Hari Libur di Tahun Tersebut
        // Format pluck: ['2026-04-10', '2026-12-25', dst]
        $holidays = Libur::whereYear('date_holiday', $year)
            ->pluck('date_holiday')
            ->toArray();

        $activeDays = 0;

        // 3. Looping dari 1 Januari sampai 31 Desember
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');

            // Skip jika hari MINGGU (Hanya masuk Senin - Sabtu)
            if ($date->isSunday()) {
                continue;
            }

            // Skip jika tanggal ini ada di tabel LIBUR
            if (in_array($dateString, $holidays)) {
                continue;
            }

            $activeDays++;
        }

        return $activeDays;
    }

    public function yearlyReport(Request $request)
    {
        // 1. Tentukan periode report (Hanya Tahun)
        // Default menggunakan tahun saat ini jika tidak ada filter
        $year = $request->year ?? date('Y');

        // 2. AMBIL DATA MASTER USER
        $users = User::orderBy('fullname', 'asc')->get();

        // 3. AMBIL & HITUNG DATA ABSENSI TAHUNAN (Hanya Check In)
        $attendances = Attendance::whereYear('attendance_date', $year)
            ->where('attendance_type', 'check_in')
            ->selectRaw('employee_id, COUNT(*) as total_hadir')
            ->groupBy('employee_id')
            ->pluck('total_hadir', 'employee_id');

        // 4. AMBIL DATA PENGAJUAN IZIN TAHUNAN (Hanya yang Approved)
        // Mencari izin yang dimulai di tahun ini ATAU selesai di tahun ini
        $izins = PengajuanIzin::where('status', 'Approved')
            ->where(function ($query) use ($year) {
                $query->whereYear('tgl_mulai', $year)
                    ->orWhereYear('tgl_selesai', $year);
            })->get();

        // 5. MAPPING HARI IZIN, SAKIT, & CUTI
        $izinMap = [];
        foreach ($izins as $izin) {
            $uid = $izin->employe_id ?? $izin->user_id;

            $start = Carbon::parse($izin->tgl_mulai);
            $end = Carbon::parse($izin->tgl_selesai);

            for ($date = $start; $date->lte($end); $date->addDay()) {
                // Pastikan HANYA menghitung hari yang jatuh pada TAHUN report ini
                if ($date->format('Y') == $year) {

                    if (!isset($izinMap[$uid])) {
                        $izinMap[$uid] = ['sakit' => 0, 'izin' => 0, 'cuti' => 0];
                    }

                    if ($izin->jenis == 'Izin Sakit') {
                        $izinMap[$uid]['sakit']++;
                    } elseif ($izin->jenis == 'Izin Keperluan') {
                        $izinMap[$uid]['izin']++;
                    } elseif ($izin->jenis == 'Cuti') {
                        $izinMap[$uid]['cuti']++;
                    }
                }
            }
        }

        // Hitung total hari efektif selama setahun sekali saja sebelum dilooping
        $hariEfektifTahunan = $this->getActiveDaysInYear($year);

        // 6. RAKIT DATA FINAL UNTUK REPORT
        $reportData = [];

        foreach ($users as $user) {
            $uid = $user->id;

            // Ambil data hadir (jika tidak ada di array, beri nilai 0)
            $hadir = $attendances->get($uid) ?? 0;

            // Ambil data izin/sakit/cuti
            $sakit = $izinMap[$uid]['sakit'] ?? 0;
            $izin = $izinMap[$uid]['izin'] ?? 0;
            $cuti = $izinMap[$uid]['cuti'] ?? 0;

            // Kalkulasi sesuai rumus
            $total_tidak_masuk = $sakit + $izin + $cuti;
            $total_masuk = $hadir - $total_tidak_masuk;

            $reportData[] = [
                'id_user' => $user->id,
                'nama_karyawan' => $user->fullname,
                'hadir_mesin' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'cuti' => $cuti,
                'total_masuk_kurangi_izin' => $total_masuk,
                'total_tidak_masuk' => $total_tidak_masuk,
                'hari_efektif' => $hariEfektifTahunan
            ];
        }

        // 7. RETURN RESPONSE
        return response()->json([
            'success' => true,
            'message' => 'Yearly report generated successfully',
            'periode' => $year,
            'data' => $reportData
        ], 200);
    }
}