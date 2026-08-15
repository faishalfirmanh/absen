<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamaahVaksin extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'name_jamaah',
        'passport_no',
        'v_code_generate',
        'date_under_name',
        'v_name_1',
        'date_v1',
        'valid_until_v1',
        'location_v1',
        'qr_full_urlcode',
        'tipe_v1',
        'tipe_v2',
        'vendor_v2',
        'date_v2',
        'valid_until_v2',
        'location_v2',
        'full_url_code_qr'
    ];
}
