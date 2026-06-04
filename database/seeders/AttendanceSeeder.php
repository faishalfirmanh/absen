<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Libur;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Setup Data Target
        $employeeId = 47; //isal 47 Ganti dengan ID user/karyawan yang kamu inginkan
        $locationId = 1;  // Asumsi ada location_id dari skema sebelumnya

        // Tentukan Bulan dan Tahun (Misal: Bulan saat ini)
        $month = '06';//Carbon::now()->month;
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

        $attendances = [];

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

            // 4. Set Waktu Check In & Check Out (Selisih 8 Jam)
            // Asumsi jam masuk pukul 08:00:00
            $checkInTime = $currentDate->copy()->setTime(8, 0, 0);
            // Jam pulang pukul 16:00:00
            $checkOutTime = $checkInTime->copy()->addHours(9);

            // Tambahkan Array Data Check In
            $attendances[] = [
                'employee_id' => $employeeId,
                'location_id' => $locationId,
                'attendance_type' => 'check_in',
                'attendance_date' => $dateString,
                'attendance_time' => $checkInTime->format('Y-m-d H:i:s'),
                'created_at' => now(),
                'updated_at' => now(),
                'submitted_latitude' => -7.51003002,
                'submitted_longitude' => 112.54197030,
            ];

            // Tambahkan Array Data Check Out
            $attendances[] = [
                'employee_id' => $employeeId,
                'location_id' => $locationId,
                'attendance_type' => 'check_out',
                'attendance_date' => $dateString,
                'attendance_time' => $checkOutTime->format('Y-m-d H:i:s'),
                'submitted_latitude' => -7.51003002,
                'submitted_longitude' => 112.54197030,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // 5. Eksekusi Bulk Insert ke Database
        if (!empty($attendances)) {
            Attendance::insert($attendances);
            $this->command->info('Berhasil insert ' . count($attendances) . ' baris absensi untuk Karyawan ID: ' . $employeeId);
        } else {
            $this->command->info('Tidak ada hari aktif untuk di-insert.');
        }
    }
}