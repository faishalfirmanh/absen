<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';
    protected $primaryKey = 'attendance_id';

    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'location_id',
        'attendance_type',
        'attendance_date',
        'submitted_latitude',
        'submitted_longitude',
        'distance_meters',
        'device_id',
        'device_model',
        'device_brand',
        'android_version',
        'app_version',
        'gps_accuracy',
        'ip_address',
        'status',
        'rejection_reason',
        'photo_url',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'attendance_time' => 'datetime',
        'submitted_latitude' => 'decimal:8',
        'submitted_longitude' => 'decimal:8',
        'distance_meters' => 'decimal:2',
        'gps_accuracy' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Relationship: Attendance belongs to User (your existing users table)
     * (I linked it to User because you already have auth on users table)
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    /**
     * Relationship: Attendance belongs to Work Location
     */
    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class, 'location_id', 'location_id');
    }
}