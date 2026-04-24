<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanIzinRequest;
use App\Models\PengajuanIzin;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\JsonResponse;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {

        $employeeId = Auth::id();
        $today = Carbon::today()->format('Y-m-d');

        $attendanceCount = Attendance::where('employee_id', $request->employee_id)
            ->where('attendance_date', $today)
            ->count();
        //dd($attendanceCount);
        $validator = Validator::make($request->all(), [
            // 'location_id' => 'required|exists:work_locations,location_id',
            'attendance_type' => [
                'required',
                'in:check_in,check_out',
                function ($attribute, $value, $fail) use ($attendanceCount) {
                    if ($attendanceCount >= 2) {
                        $fail('Anda sudah melakukan check-in dan check-out hari ini. Tidak dapat absen lagi.');
                    } elseif ($attendanceCount === 0 && $value !== 'check_in') {
                        $fail('Belum ada absensi hari ini. Harus menggunakan attendance_type = check_in.');
                    } elseif ($attendanceCount === 1 && $value !== 'check_out') {
                        $fail('Anda sudah check_in hari ini. Harus menggunakan attendance_type = check_out.');
                    }
                },
            ],
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
            'photo' => 'required|image|mimes:jpeg,png,jpg,svg|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->toArray(),
            ], 500);
        }


        $format_date_no = str_replace("-", "", $today);
        //dd($format_date_no);
        $photo = $request->file('photo');
        //$originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $photo->getClientOriginalExtension();
        $newFileName = $request->employee_id . "_" . $format_date_no . "_" . $request->attendance_type . "." . 'webp';
        //$originalName . '_' . time() . '_' . Str::random(12) . '.' . $extension;
        $folderPath = env('DB_USERNAME') == 'root' ? 'uploads/photos_absence/' : 'app/public/uploads/photos_absence/';//check if server local or server prod
        $fullPath = $folderPath . $newFileName;
        $image = Image::make($photo->getRealPath());
        //dd($newFileName);
        $image->resize(800, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $maxSizeKB = 100;   // target maksimal 100 KB
        $quality = 85;

        while (true) {
            $encoded = $image->encode($extension, $quality);

            if (strlen($encoded) / 1024 <= $maxSizeKB || $quality <= 10) {
                break;
            }

            $quality -= 5;   // turunkan kualitas 5 poin setiap iterasi
        }

        // Simpan ke storage (public disk)
        Storage::disk('public')->put($fullPath, $encoded);
        $url = Storage::url($fullPath);

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

        if ($distance > 25) {
            return response()->json([
                'success' => false,
                'message' => 'gagal jarak lebih dari 25',
                'distance_meters' => round($distance, 2),
            ], 400);
        }

        $attendance = Attendance::create([
            'employee_id' => $employee_id,
            'location_id' => $request->location_id,
            'attendance_type' => $request->attendance_type,
            'attendance_date' => $today,
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
            'distance_meters' => round($distance, 2),
            'image' => [
                'file_name' => $newFileName,
                'path' => $fullPath,
                'url' => $url,
                'size_kb' => round(strlen($encoded) / 1024, 2),
                'quality' => $quality,
            ]
        ], 201);
    }

    public function getImage($user_id, $date, $limit = 'all')
    {
        // Base path & URL
        $upload_path = env('DB_USERNAME') == 'root' ? public_path('storage/uploads/photos_absence/') : asset('storage/app/public/uploads/photos_absence/');

        $base_url = env('DB_USERNAME') == 'root' ? asset('storage/uploads/photos_absence/') : asset('storage/app/public/uploads/photos_absence/');

        // Pattern: {user_id}_{date}_*.png
        $pattern = $upload_path . $user_id . '_' . $date . '*.webp';
        $files = glob($pattern);

        if (empty($files)) {
            return response()->json([
                'status' => false,
                'message' => 'No images found',
                'user_id' => $user_id,
                'date' => $date,
            ], 404);
        }

        // Sort ascending (check_in before check_out)
        sort($files);

        // Apply limit — get LAST N files
        if ($limit !== 'all' && is_numeric($limit)) {
            $files = array_slice($files, -intval($limit));
        }

        // Build image list
        $images = [];
        foreach ($files as $file) {
            $filename = basename($file);

            $type = '';
            if (str_contains($filename, 'check_in'))
                $type = 'check_in';
            if (str_contains($filename, 'check_out'))
                $type = 'check_out';

            $images[] = [
                'filename' => $filename,
                'type' => $type,
                'url' => $base_url . '/' . $filename,
            ];
        }

        return response()->json([
            'status' => true,
            'user_id' => $user_id,
            'date' => $date,
            'limit' => $limit,
            'total' => count($images),
            'images' => $images,
        ]);
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


    public function storeIzin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'nama_custom' => 'required|string|max:150',
            'jenis' => 'required|in:Cuti,Izin Sakit,Izin Keperluan',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string',
            'bukti_sakit' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // max 5MB
            //'kota_surat' => 'required|string|max:100',
            //'tgl_surat' => 'required|date',
            // 'isi_surat_custom' => 'nullable|string',
            //'ttd_user' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',     // max 2MB
        ], [
            'jenis.in' => 'Jenis hanya boleh Cuti, Izin Sakit, atau Izin Keperluan.',
            'tgl_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }


        //$request['ttd_user'] = '333';//$request->data_user->fullname;


        $validated = $validator->validated();
        $data = $this->submitIzin($validated, $request);
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil disubmit',
            'data' => $data
        ], 201);

    }


    public function updateApproval(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:pengajuan_izin,id',
            'status' => 'required|string|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $pengajuan = PengajuanIzin::where('id', $request->id)->first();
        $pengajuan->status = $request->status;
        $pengajuan->save();

        return $this->autoResponse($pengajuan);

    }


    private function submitIzin(array $validated, Request $request)
    {

        // Set user_id dari user yang login (paling aman)
        $validated['user_id'] = $request->employee_id;

        $validated['divisi_custom'] = $request->data_user->role;
        $validated['jabatan_custom'] = 'Staff';
        $validated['nama_custom'] = $request->data_user->fullname;
        // Default status
        $validated['status'] = 'Pending';

        if ($request->hasFile('bukti_sakit')) {
            // $buktiPath = $request->file('bukti_sakit')
            //     ->store('bukti_sakit', 'public'); // simpan di storage/app/public/bukti_sakit
            // $validated['bukti_sakit'] = $buktiPath;

            $today = Carbon::today()->format('Y-m-d');
            $format_date_no = str_replace("-", "", $today);
            $photo = $request->file('bukti_sakit');
            $extension = $photo->getClientOriginalExtension();
            $newFileName = $request->employee_id . "_" . $format_date_no . "_" . 'izin' . "." . 'webp';

            $folderPath = env('DB_USERNAME') == 'root' ? 'uploads/izin_sakit/' : 'app/public/uploads/izin_sakit/';//check if server local or server prod
            $fullPath = $folderPath . $newFileName;
            $image = Image::make($photo->getRealPath());
            $image->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $maxSizeKB = 100;   // target maksimal 100 KB
            $quality = 85;

            while (true) {
                $encoded = $image->encode($extension, $quality);

                if (strlen($encoded) / 1024 <= $maxSizeKB || $quality <= 10) {
                    break;
                }
                $quality -= 5;
            }

            Storage::disk('public')->put($fullPath, $encoded);
            $url = Storage::url($fullPath);
            $validated['bukti_sakit'] = url('/') . $url;
        }

        $pengajuan = PengajuanIzin::create($validated);

        return $pengajuan;
    }
}