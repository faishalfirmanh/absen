<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralPaketUmroh extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'tgl_keberangkatan',
        'nama_program',
        'nama_maskapai',
        'rute',
        'program_hari',
        'total_seat',
        // 'total_jamaah',
        'available',
        // 'miqat_awal',
        // 'hotel_madinah',
        // 'night_madinah',
        // 'hotel_makkah',
        // 'night_makkah',
        // 'harga',
        // 'harga_triple',
        // 'harga_double',
        // 'tambahan_layanan_fasilitas'
    ];

    public function detailsHotels()
    {
        return $this->hasMany(HotelDetailPaket::class, 'general_paket_id');
    }
}
