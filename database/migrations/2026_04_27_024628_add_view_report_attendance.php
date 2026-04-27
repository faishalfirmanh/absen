<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class AddViewReportAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS `view_absensi_karyawan`');

        DB::statement("
            CREATE VIEW `view_absensi_karyawan` AS
            SELECT 
                u.id AS employee_id,
                u.fullname,
                a.attendance_date AS tanggal,
                CASE 
                    -- Tidak ada record absen sama sekali
                    WHEN a.attendance_date IS NULL THEN 'Lupa Absen / Tidak Masuk'
                    
                    -- Hanya check_in (tidak ada check_out)
                    WHEN COUNT(CASE WHEN a.attendance_type = 'check_in' THEN 1 END) > 0 
                         AND COUNT(CASE WHEN a.attendance_type = 'check_out' THEN 1 END) = 0 
                    THEN 'Kurang Check Out'
                    
                    -- Hanya check_out (tidak ada check_in)
                    WHEN COUNT(CASE WHEN a.attendance_type = 'check_in' THEN 1 END) = 0 
                         AND COUNT(CASE WHEN a.attendance_type = 'check_out' THEN 1 END) > 0 
                    THEN 'Kurang Check In'
                    
                    -- Lengkap → hitung selisih jam
                    ELSE CONCAT(
                            TIMESTAMPDIFF(HOUR, 
                                MIN(CASE WHEN a.attendance_type = 'check_in' THEN a.attendance_time END),
                                MAX(CASE WHEN a.attendance_type = 'check_out' THEN a.attendance_time END)
                            ), ' jam ',
                            MOD(TIMESTAMPDIFF(MINUTE, 
                                MIN(CASE WHEN a.attendance_type = 'check_in' THEN a.attendance_time END),
                                MAX(CASE WHEN a.attendance_type = 'check_out' THEN a.attendance_time END)
                            ), 60), ' menit'
                         )
                END AS keterangan,
                
                -- Info tambahan (bisa dihapus kalau tidak butuh)
                COUNT(CASE WHEN a.attendance_type = 'check_in' THEN 1 END) AS jumlah_check_in,
                COUNT(CASE WHEN a.attendance_type = 'check_out' THEN 1 END) AS jumlah_check_out

            FROM users u
            LEFT JOIN attendances a 
                ON u.id = a.employee_id
            GROUP BY u.id, u.fullname, a.attendance_date
            ORDER BY a.attendance_date DESC, u.fullname
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        DB::statement('DROP VIEW IF EXISTS `view_absensi_karyawan`');
    }
}
