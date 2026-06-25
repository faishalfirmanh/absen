<?php

namespace App\Models\Alhidayah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataJamaah extends Model
{
    use HasFactory;

    protected $connection = 'mysql_second';
    protected $table = 'data_jamaah';

    public function getProv()
    {
        return $this->belongsTo(Prov::class, 'location_prov', 'id');
    }

    public function getCity()
    {
        return $this->belongsTo(City::class, 'location_city', 'id');
    }

    public function getKec()
    {
        return $this->belongsTo(Subdis::class, 'location_disct', 'id');
    }

    public function getVillage()
    {
        return $this->belongsTo(Village::class, 'location_village', 'id');
    }
}
