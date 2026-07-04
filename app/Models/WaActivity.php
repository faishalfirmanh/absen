<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaActivity extends Model
{
    use HasFactory;


    protected $fillable = [
        'nama_karyawan',
        'waktu_scan',
        'nama_room',
        'payload_chat',
        'user_id',
        'plan_Kerja'
    ];

    public $timestamps = false;

    public function getUsers()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
