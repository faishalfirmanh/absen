<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Libur;
use App\Models\PengajuanIzin;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Log;
class AttendanceSeederUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    //Perlu di cek lagi
    public function run()
    {
        $today = Carbon::now(); // pastikan config/app.php timezone = Asia/Jakarta
        $firstDay = $today->copy()->startOfMonth()->toDateString();
        $lastDay = $today->copy()->endOfMonth()->toDateString();

        $users = User::where('is_active', 1)->get(['id', 'location']);

        // ASUMSI nama kolom tanggal_mulai/tanggal_selesai/status — sesuaikan
        // dengan skema PengajuanIzin milikmu yang sebenarnya
        $pengajuanByUser = PengajuanIzin:://where('status', 'approved')
            where('tgl_mulai', '<=', $lastDay)
            ->where('tgl_selesai', '>=', $firstDay)
            ->get()
            ->groupBy('employee_id');

        $holidays = Libur::pluck('date_holiday')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $dateRange = collect(CarbonPeriod::create($firstDay, $lastDay))
            ->filter(fn($date) => !$date->isSunday() && !in_array($date->format('Y-m-d'), $holidays))
            ->map(fn($date) => $date->format('Y-m-d'))
            ->values();

        $existingAttendance = Attendance::whereBetween('attendance_date', [$firstDay, $lastDay])
            ->get()
            ->groupBy(fn($a) => $a->employee_id . '_' . $a->attendance_date);

        foreach ($users as $user) {
            $izinDates = collect();
            foreach ($pengajuanByUser->get($user->id, collect()) as $izin) {
                $izinDates = $izinDates->merge(
                    collect(CarbonPeriod::create($izin->tanggal_mulai, $izin->tanggal_selesai))
                        ->map(fn($d) => $d->format('Y-m-d'))
                );
            }

            foreach ($dateRange as $date) {
                if ($izinDates->contains($date)) {
                    continue; // user sedang izin/cuti di tanggal ini
                }
                $recordsHariIni = $existingAttendance->get($user->id . '_' . $date, collect());
                $sudahCheckIn = $recordsHariIni->firstWhere('attendance_type', 'check_in');
                $sudahCheckOut = $recordsHariIni->firstWhere('attendance_type', 'check_out');

                DB::beginTransaction();
                try {
                    $this->command->info($recordsHariIni);
                    // if (!$sudahCheckIn) {
                    //     Attendance::create([
                    //         'employee_id' => $user->id,
                    //         'location_id' => $user->location,
                    //         'attendance_date' => $date,
                    //         'attendance_type' => 'check_in',
                    //         'submitted_latitude' => -7.51003002,
                    //         'submitted_longitude' => 112.54197030,
                    //         'attendance_time' => Carbon::parse($date . ' 08:00:00')->format('Y-m-d H:i:s'),
                    //         'status' => 'approved',
                    //     ]);
                    // }

                    // if (!$sudahCheckOut) {
                    //     $jamCheckIn = $sudahCheckIn
                    //         ? Carbon::parse($sudahCheckIn->attendance_time)
                    //         : Carbon::parse($date . ' 08:00:00');

                    //     Attendance::create([
                    //         'employee_id' => $user->id,
                    //         'location_id' => $user->location,
                    //         'attendance_date' => $date,
                    //         'attendance_type' => 'check_out',
                    //         'submitted_latitude' => -7.51003002,
                    //         'submitted_longitude' => 112.54197030,
                    //         'attendance_time' => $jamCheckIn->copy()->addHours(8)->format('Y-m-d H:i:s'),
                    //         'status' => 'approved',
                    //     ]);
                    // }
                    DB::commit();
                    $this->command->info('SUKSES RUN sEEDER ' . $user->username);
                } catch (\Throwable $th) {
                    DB::rollBack();


                    Log::error('Gagal isi absen otomatis', [
                        'employee_id' => $user->id ?? null,
                        'date' => $date ?? null,
                        'message' => $th->getMessage(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'trace' => $th->getTraceAsString(),
                    ]);
                    $this->command->error(sprintf(
                        'Gagal user %s tanggal %s: %s (%s:%s)',
                        $user->id ?? '-',
                        $date ?? '-',
                        $th->getMessage(),
                        $th->getFile(),
                        $th->getLine()
                    ));
                }

            }
        }
    }
}
