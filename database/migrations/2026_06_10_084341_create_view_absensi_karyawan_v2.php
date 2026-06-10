<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateViewAbsensiKaryawanV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Menggunakan DB::statement untuk mengeksekusi raw query MySQL
        DB::statement("
            CREATE OR REPLACE VIEW `view_absensi_karyawan_v2` AS
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
                
                -- Logika absen_terakhir: Prioritaskan check_out, jika NULL ambil check_in
                COALESCE(
                    MAX(CASE WHEN a.attendance_type = 'check_out' THEN a.attendance_time END),
                    MAX(CASE WHEN a.attendance_type = 'check_in' THEN a.attendance_time END)
                ) AS absen_terakhir,
                
                -- Info tambahan
                COUNT(CASE WHEN a.attendance_type = 'check_in' THEN 1 END) AS jumlah_check_in,
                COUNT(CASE WHEN a.attendance_type = 'check_out' THEN 1 END) AS jumlah_check_out

            FROM users u
            LEFT JOIN attendances a 
                ON u.id = a.employee_id
            GROUP BY 
                u.id, 
                u.fullname, 
                a.attendance_date
            ORDER BY 
                a.attendance_date DESC, 
                u.fullname
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Menghapus view jika dilakukan rollback
        DB::statement("DROP VIEW IF EXISTS `view_absensi_karyawan_v2`");
    }
}