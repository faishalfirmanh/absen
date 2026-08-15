<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;

use App\Models\JamaahVaksin;
use App\Models\WaActivity;




class JamaahVRepo extends BaseRepository
{

    public $model;
    public function __construct(JamaahVaksin $model)
    {
        $this->model = $model;
    }

}
