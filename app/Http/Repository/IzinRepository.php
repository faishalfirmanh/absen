<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;

use App\Models\PengajuanIzin;




class IzinRepository extends BaseRepository
{

    public $model;
    public function __construct(PengajuanIzin $model)
    {
        $this->model = $model;
    }

}
