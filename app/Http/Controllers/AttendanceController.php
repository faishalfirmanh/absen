<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\WorkLocation;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:work_locations,location_id',
            'attendance_type' => 'required|in:check_in,check_out',
            'submitted_latitude' => 'required|numeric',
            'submitted_longitude' => 'required|numeric',
            'device_id' => 'nullable|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'device_brand' => 'nullable|string|max:50',
            'android_version' => 'nullable|string|max:20',
            'app_version' => 'nullable|string|max:20',
            'gps_accuracy' => 'nullable|numeric',
            'photo_url' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $employee_id = $request->employee_id;           // ← From Middleware
        $location = WorkLocation::findOrFail($request->location_id);

        // Calculate distance (Haversine formula - in meters)
        $distance = $this->haversineDistance(
            $location->latitude,
            $location->longitude,
            $request->submitted_latitude,
            $request->submitted_longitude
        );

        // Decide status
        $status = $distance <= $location->radius_meters ? 'approved' : 'rejected';
        $rejection_reason = $status === 'rejected' ? 'Di luar radius kantor' : null;

        if ($distance > 20) {
            return response()->json([
                'success' => false,
                'message' => 'gagal jarak lebih dari 20',
                'distance_meters' => round($distance, 2),
            ], 400);
        }

        $attendance = Attendance::create([
            'employee_id' => $employee_id,
            'location_id' => $request->location_id,
            'attendance_type' => $request->attendance_type,
            'attendance_date' => now()->toDateString(),
            'submitted_latitude' => $request->submitted_latitude,
            'submitted_longitude' => $request->submitted_longitude,
            'distance_meters' => round($distance, 2),
            'device_id' => $request->device_id,
            'device_model' => $request->device_model,
            'device_brand' => $request->device_brand,
            'android_version' => $request->android_version,
            'app_version' => $request->app_version,
            'gps_accuracy' => $request->gps_accuracy,
            'status' => $status,
            'rejection_reason' => $rejection_reason,
            'photo_url' => $request->photo_url,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => $status === 'approved'
                ? 'Absensi berhasil dicatat ✅'
                : 'Absensi DITOLAK ❌ (di luar area kantor)',
            'data' => $attendance,
            'distance_meters' => round($distance, 2)
        ], 201);
    }

    /**
     * Haversine Distance Formula (Earth radius in meters)
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}