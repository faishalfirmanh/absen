<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $table = 'overtime';

    protected $fillable = [
        'attendance_id',
        'overtime_hour',
    ];

    public $timestamps = false;
}