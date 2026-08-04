<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;

use App\Models\WorkLocation;


class WorkLocationRepo extends BaseRepository
{

    public $model;
    public function __construct(WorkLocation $model)
    {
        $this->model = $model;
    }

}
