<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanIzin extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_izin';

    protected $fillable = [
        'id',
        'user_id',
        'nama_custom',
        'jenis',
        'tgl_mulai',
        'tgl_selesai',
        'alasan',
        'bukti_sakit',
        'kota_surat',
        'tgl_surat',
        'isi_surat_cust',
        'ttd_user',
        'status ',
        'tgl_approval',
        'created_at',
        'divisi_custom',
        'jabatan_custom'
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'tgl_surat' => 'date',
        'tgl_approval' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
