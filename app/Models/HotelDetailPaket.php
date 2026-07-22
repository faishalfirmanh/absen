<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelDetailPaket extends Model
{
    use HasFactory;

    protected $fillable = [
        'general_paket_id',
        'total_jamaah',//total jamaah tiap hotel
        'miqat_awal',
        'hotel_madinah',
        'night_madinah',
        'hotel_makkah',
        'night_makkah',
        'harga',
        'harga_triple',
        'harga_double',
        'tambahan_layanan_fasilitas',
    ];

    public function getParent()
    {
        return $this->hasOne(GeneralPaketUmroh::class, 'id', 'general_paket_id');
    }
}
