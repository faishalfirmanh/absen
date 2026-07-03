<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;

use App\Models\WaActivity;




class WaActivityRepository extends BaseRepository
{

    public $model;
    public function __construct(WaActivity $model)
    {
        $this->model = $model;
    }

}
