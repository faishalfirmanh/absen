<?php

namespace App\Http\Repository;

use App\Http\Repository\BaseRepository;

use App\Models\GeneralPaketUmroh;

class PaketGeneralRepo extends BaseRepository
{

    public $model;
    public function __construct(GeneralPaketUmroh $model)
    {
        $this->model = $model;
    }

    public function getDataPaketnya($model)
    {
        return $this->model->with($model)->get();
    }

}
