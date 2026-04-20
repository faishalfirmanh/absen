<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAttendance extends Model
{
    /**
     * The table associated with the model.
     * This is a VIEW, not a real table.
     */
    protected $table = 'v_daily_attendance';

    /**
     * Primary key is not needed for views, but we set it to avoid errors
     */
    protected $primaryKey = null;

    /**
     * Views are read-only (no insert/update/delete)
     */
    public $incrementing = false;
    public $timestamps = false;           // view has no created_at/updated_at

    /**
     * Columns that can be used in queries (for IDE support & security)
     */
    protected $fillable = [
        'id',
        'fullname',
        'username',
        'location_name',
        'attendance_date',
        'attendance_type',
        'attendance_time',
        'submitted_latitude',
        'submitted_longitude',
        'distance_meters',
        'device_id',
        'device_model',
        'device_brand',
        'android_version',
        'app_version',
        'gps_accuracy',
        'status',
        'rejection_reason',
    ];

    /**
     * Cast attributes to proper types
     */
    protected $casts = [
        'attendance_date' => 'date',
        'attendance_time' => 'datetime',
        'submitted_latitude' => 'decimal:8',
        'submitted_longitude' => 'decimal:8',
        'distance_meters' => 'decimal:2',
        'gps_accuracy' => 'decimal:2',
    ];

    /**
     * Optional: Scope for today's attendance only
     */
    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', now()->toDateString());
    }

    /**
     * Optional: Scope for approved only
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}