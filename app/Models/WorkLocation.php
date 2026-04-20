<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;
class WorkLocation extends Model
{
    use HasFactory;

    protected $table = 'work_locations';
    protected $primaryKey = 'location_id';

    // Laravel will automatically manage created_at & updated_at
    public $timestamps = true;

    protected $fillable = [
        'location_name',
        'latitude',
        'longitude',
        'radius_meters',
        'address',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius_meters' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: One location has many attendances
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'location_id', 'location_id');
    }
}