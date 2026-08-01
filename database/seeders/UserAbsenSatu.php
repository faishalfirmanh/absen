<?php

namespace Database\Seeders;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserAbsenSatu extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    //Belum selesai
    public function run()
    {
        $date = Carbon::now();
        Attendance::create([
            'employee_id' => 2,
            'location_id' => 1,
            'attendance_date' => $date,
            'attendance_type' => 'check_in',
            'submitted_latitude' => -7.51003002,
            'submitted_longitude' => 112.54197030,
            'attendance_time' => Carbon::today()->setTime(8, 32, 0),
            'status' => 'approved',
        ]);
    }
}
