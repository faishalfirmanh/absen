<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Repository\AttendanceRepository;
use App\Http\Repository\IzinRepository;
use App\Http\Repository\PaketGeneralRepo;
use App\Http\Repository\WaActivityRepository;
use App\Models\User;
use App\Models\Attendance;
use App\Models\PengajuanIzin;
use App\Models\Libur;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;

use Illuminate\Support\Str;
class ReportController extends Controller
{

    use ApiResponse;


    protected $repo, $repo_izin, $repo_wa, $repo_paket;

    public function __construct(AttendanceRepository $repo, IzinRepository $repo_izin, WaActivityRepository $repo_wa, PaketGeneralRepo $repo_paket)
    {
        $this->repo = $repo;
        $this->repo_izin = $repo_izin;
        $this->repo_wa = $repo_wa;
        $this->repo_paket = $repo_paket;
    }


    public function listPaket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|in:namiroh123#'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        if (!Str::contains($request->key, 'namiroh123#')) {
            return $this->error('Key tidak valid.', 422);
        }

        $datanya = $this->repo_paket->getDataPaketnya('detailsHotels');
        return $this->autoResponse($datanya);
    }

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


    public function GetDetailWa($id)
    {
        $data = $this->repo_wa->WhereDataWith(['getUsers'], ['id' => $id])->first();
        return $this->autoResponse($data);
    }


    private static function getWorkingDaysOfMonth(int $year, int $month): array
    {
        // Holidays → keyed array untuk O(1) lookup (bukan in_array O(n))
        $holidays = Libur::whereYear('date_holiday', $year)
            ->whereMonth('date_holiday', $month)
            ->pluck('date_holiday')
            ->mapWithKeys(fn($d) => [Carbon::parse($d)->format('Y-m-d') => true])
            ->toArray();

        $result = [];
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day);
            $dateStr = $date->format('Y-m-d');

            // BUG FIX #3: Mon–Sat (skip Sunday only), bukan Mon–Fri
            if (!$date->isSunday() && !isset($holidays[$dateStr])) {
                $result[] = $dateStr;
            }
        }

        return $result;
    }

    /**
     * BUG FIX #2 — tambah parameter $year
     * BUG FIX #6 — jadikan static
     * BUG FIX #7 — delegasi ke getWorkingDaysOfMonth, tidak duplikasi logika
     */


    // ============================================================
    // MONTHLY REPORT
    // ============================================================
    public function monthlyReport(Request $request)
    {
        // ─── 1. PERIODE ──────────────────────────────────────────
        $month = (int) ($request->month ?? date('n'));
        $year = (int) ($request->year ?? date('Y'));

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // BUG FIX #7 — panggil sekali, pakai untuk dua tujuan
        $workingDays = self::getWorkingDaysOfMonth($year, $month); // array Y-m-d
        $hariEfektif = count($workingDays);

        // ─── 2. USER ─────────────────────────────────────────────
        $users = User::orderBy('fullname')->get()->keyBy('id');
        $userIds = $users->keys()->toArray();

        // ─── 3. ABSENSI ──────────────────────────────────────────
        // BUG FIX #1 (root cause) — pakai ->toBase() agar query mengembalikan
        // stdClass, bukan Eloquent model. Dengan ini attendance_date dan
        // attendance_time PASTI string, tidak pernah di-cast ke Carbon object.
        $rawAttendances = Attendance::whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->whereIn('attendance_type', ['check_in', 'check_out'])
            ->whereIn('employee_id', $userIds)
            ->orderBy('attendance_date')
            ->orderBy('attendance_time')
            ->select('employee_id', 'attendance_date', 'attendance_type', 'attendance_time')
            ->toBase()   // ← bypass Eloquent casting → stdClass, bukan model
            ->get();

        /*
         * Struktur hasil:
         * $attendanceByUserDate[uid][Y-m-d]['check_in']  = 'HH:MM:SS'
         * $attendanceByUserDate[uid][Y-m-d]['check_out'] = 'HH:MM:SS'
         */
        $attendanceByUserDate = [];
        foreach ($rawAttendances as $row) {
            // BUG FIX #1 — cast eksplisit sebagai lapisan kedua keamanan
            $uid = (string) $row->employee_id;
            $date = (string) $row->attendance_date;
            $type = (string) $row->attendance_type;
            $time = (string) $row->attendance_time;

            // Normalisasi: jaga-jaga jika DB kembalikan 'Y-m-d H:i:s'
            if (strlen($date) > 10) {
                $date = substr($date, 0, 10);
            }

            $attendanceByUserDate[$uid][$date][$type] = $time;
        }

        // ─── 4. PENGAJUAN IZIN ───────────────────────────────────
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $periodEnd = Carbon::createFromDate($year, $month, $daysInMonth)->endOfDay();

        $izins = PengajuanIzin::where('status', 'Approved')
            ->whereIn('user_id', $userIds)
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->where('tgl_mulai', '<=', $periodEnd)
                    ->where('tgl_selesai', '>=', $periodStart);
            })
            ->get(['user_id', 'tgl_mulai', 'tgl_selesai', 'jenis']);

        // ─── 5. MAPPING HARI IZIN / SAKIT / CUTI ────────────────
        $izinMap = [];  // [uid => ['sakit'=>0, 'izin'=>0, 'cuti'=>0]]
        $izinDates = [];  // [uid => ['Y-m-d' => true, ...]]

        foreach ($izins as $izin) {
            $uid = (string) ($izin->user_id ?? $izin->user_id ?? null);
            if (!$uid)
                continue;

            $start = Carbon::parse($izin->tgl_mulai);
            $end = Carbon::parse($izin->tgl_selesai);

            // Klip ke batas bulan laporan
            if ($start->lt($periodStart))
                $start = $periodStart->copy();
            if ($end->gt($periodEnd))
                $end = $periodEnd->copy();

            // PHP 7.4: ??= (null coalescing assignment)
            $izinMap[$uid] ??= ['sakit' => 0, 'izin' => 0, 'cuti' => 0];
            $izinDates[$uid] ??= [];

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dateKey = $date->format('Y-m-d');

                switch ($izin->jenis) {
                    case 'Izin Sakit':
                        $izinMap[$uid]['sakit']++;
                        break;
                    case 'Izin Keperluan':
                        $izinMap[$uid]['izin']++;
                        break;
                    case 'Cuti':
                        $izinMap[$uid]['cuti']++;
                        break;
                }

                $izinDates[$uid][$dateKey] = true;
            }
        }

        // ─── 6. RAKIT DATA FINAL ─────────────────────────────────
        $reportData = [];

        foreach ($users as $uid => $user) {
            $uid = (string) $uid;
            $userAttDates = $attendanceByUserDate[$uid] ?? [];
            $userIzinDates = $izinDates[$uid] ?? [];

            // 6a. Hadir mesin (ada check_in di mesin)
            $totalHadir = 0;
            foreach ($userAttDates as $types) {
                if (isset($types['check_in']))
                    $totalHadir++;
            }

            // 6b. Total jam masuk dari check_in–check_out (desimal jam)
            $totalMenit = 0;
            foreach ($userAttDates as $types) {
                if (isset($types['check_in'], $types['check_out'])) {
                    $in = Carbon::createFromTimeString($types['check_in']);
                    $out = Carbon::createFromTimeString($types['check_out']);
                    if ($out->gt($in)) {
                        $totalMenit += $in->diffInMinutes($out);
                    }
                }
            }
            $totalJamMasuk = round($totalMenit / 60, 2);

            // 6c. Tidak hadir mesin = hari kerja tanpa check_in & tanpa izin
            $tidakHadirMesin = 0;
            foreach ($workingDays as $wDay) {
                if (!isset($userAttDates[$wDay]['check_in']) && !isset($userIzinDates[$wDay])) {
                    $tidakHadirMesin++;
                }
            }

            // 6d. Ringkasan izin
            $sakit = $izinMap[$uid]['sakit'] ?? 0;
            $izin = $izinMap[$uid]['izin'] ?? 0;
            $cuti = $izinMap[$uid]['cuti'] ?? 0;

            $totalTidakMasuk = $sakit + $izin + $cuti;

            // BUG FIX #5 — clamp ke 0, hasil tidak boleh negatif
            $totalMasuk = max(0, $totalHadir - $totalTidakMasuk);

            $reportData[] = [
                'id_user' => $uid,
                'nama_karyawan' => $user->fullname,
                'hadir_mesin' => $totalHadir,
                'total_jam_masuk' => $totalJamMasuk,
                'tidak_hadir_mesin' => $tidakHadirMesin,
                'sakit' => $sakit,
                'izin' => $izin,
                'cuti' => $cuti,
                'total_masuk_kurangi_izin' => $totalMasuk,
                'total_tidak_masuk' => $totalTidakMasuk,
                'hari_efektif' => $hariEfektif,
            ];
        }

        // ─── 7. RESPONSE ─────────────────────────────────────────
        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'periode' => sprintf('%04d-%02d', $year, $month),
            'data' => $reportData,
        ]);
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
            $uid = $izin->user_id ?? $izin->user_id;

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