<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;
use App\Models\Attendance;




class AttendanceRepository extends BaseRepository
{

    public $model;
    public function __construct(Attendance $model)
    {
        $this->model = $model;
    }

}
