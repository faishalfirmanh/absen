<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;
use App\Models\Attendance;


use Illuminate\Support\Carbon;

class AttendanceRepository extends BaseRepository
{

    public $model;
    public function __construct(Attendance $model)
    {
        $this->model = $model;
    }

    public function whereDateFilter($request)
    {
        dd($request);
        // $startDate = ;
        // $endDate = ;
        // $get = $this->model->with('employee')->whereBetween('attendance_date', [$startDate, $endDate])->get();
        // return $get;
    }

}
