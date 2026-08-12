<?php

namespace App\Http\Controllers;

use App\Http\Repository\AttendanceRepository;
use App\Http\Repository\IzinRepository;

use App\Http\Repository\WorkLocationRepo;
use App\Models\Overtime;
use App\Models\PengajuanIzin;
use App\Traits\ApiResponse;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\WorkLocation;

use Log;
use Validator;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;

use Illuminate\Support\Facades\DB;
class AttendanceController extends Controller
{
    use ApiResponse;


    protected $repo, $repo_izin, $repo_location;

    public function __construct(
        AttendanceRepository $repo,
        IzinRepository $repo_izin,
        WorkLocationRepo $repo_location
    ) {
        $this->repo = $repo;
        $this->repo_izin = $repo_izin;
        $this->repo_location = $repo_location;
    }

    public function sendPesan()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => '6281336953950',
                'message' => 'test message',
                'countryCode' => '62', //optional
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ad3YSZeyfGFkeF4Vo6Dt' //change TOKEN to your actual token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    private function formatJamMenit(int $totalMenit): string
    {
        $jam = intdiv($totalMenit, 60);
        $menit = $totalMenit % 60;
        return sprintf('%d:%02d', $jam, $menit);
    }


    public function getLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|in:namiroh123',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 400);
        }

        $data = $this->repo_location->whereData([])->get();
        return $this->autoResponse($data);
    }


    function hitungOvertimeTitikMenit(string $jamKerja, int $jamStandar = 8): string
    {
        [$jam, $menit] = explode(':', $jamKerja);
        $totalMenitKerja = ((int) $jam * 60) + (int) $menit;
        $totalMenitStandar = $jamStandar * 60;

        $selisihMenit = $totalMenitKerja - $totalMenitStandar;

        if ($selisihMenit <= 0) {
            return '0.00'; // tidak ada overtime / kurang dari standar
        }

        $jamOvertime = intdiv($selisihMenit, 60);
        $menitOvertime = $selisihMenit % 60;

        return sprintf('%d.%02d', $jamOvertime, $menitOvertime);
    }

    public function store(Request $request)
    {
        $ips = $request->ips();
        $today = Carbon::today()->format('Y-m-d');
        
        // =================================================================
        // 1. Validasi Request
        // =================================================================
        $attendanceCount = Attendance::where('employee_id', $request->employee_id)
            ->where('attendance_date', $today)
            ->count();

 Log::info($request->employee_id . ' absen date ' . $today . " type " . $request->attendance_type . " count " . $attendanceCount);
 
        $validator = Validator::make($request->all(), [
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
            'location_id' => 'required',
            'device_id' => 'nullable|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'device_brand' => 'nullable|string|max:50',
            'android_version' => 'nullable|string|max:20',
            'app_version' => 'nullable|string|max:20',
            'gps_accuracy' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->toArray(),
            ], 400);
        }

        // =================================================================
        // 2. Cek Jarak Lokasi (GPS)
        // =================================================================
        $location = WorkLocation::findOrFail($request->location_id);
        $distance = $this->haversineDistance(
            $location->latitude,
            $location->longitude,
            $request->submitted_latitude,
            $request->submitted_longitude
        );

        if ($distance > $location->radius_meters) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: jarak lebih dari 25 meter.',
                'distance_meters' => round($distance, 2),
            ], 400);
        }

        $status = $distance <= $location->radius_meters ? 'approved' : 'rejected';
        $rejection_reason = $status === 'rejected' ? 'Di luar radius kantor' : null;
        $time_now = Carbon::now('Asia/Jakarta');
        $selisihJam = null;
        $sel_jam_v1 = null;

        if (config('app.config_limit_absen')) {//JIKA BERNILAI FALSE TIDAK DI JALANKAN
            if ($request->attendance_type == 'check_out') {

                $checkInUser = Attendance::where('employee_id', $request->employee_id)
                    ->where('attendance_date', $today)
                    ->where('attendance_type', 'check_in')->first();
                $checkinTime = $checkInUser->attendance_time->setTimezone('Asia/Jakarta')
                    ?? Carbon::parse($checkInUser->getRawOriginal('attendance_time'), 'Asia/Jakarta');
                // Lebih aman & pasti benar: parse ulang dari raw value
                $checkinTime = Carbon::parse($checkInUser->getRawOriginal('attendance_time'), 'Asia/Jakarta');

                $selisihMenit = $checkinTime->diffInMinutes($time_now);
                $sel_jam_v1 = round($selisihMenit / 60, 2);

                if ($sel_jam_v1 <= 8) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal: checkout pulang, kurang dari 8 jam',
                        'waktu' => $selisihJam,
                    ], 400);
                }
                $selisihJam = self::formatJamMenit($selisihMenit);
            }
        }

        // =================================================================
        // 3. DATABASE TRANSACTION (Simpan Data Absensi)
        // =================================================================
        DB::beginTransaction();

        try {
            $attendance = Attendance::create([
                'employee_id' => $request->employee_id,
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
                'notes' => $request->notes,
                'ip_address' => $ips[0] ?? null,
            ]);


            if (env('CONFIG_LIMIT_ABSEN')) {//JIKA BERNILAI FALSE TIDAK DI JALANKAN
                if ($request->attendance_type == 'check_out' && $sel_jam_v1 >= 8) {
                    $final_overtima = self::hitungOvertimeTitikMenit($selisihJam);
                    Overtime::create([
                        'attendance_id' => $attendance->attendance_id,
                        'overtime_hour' => round($final_overtima, 2),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $status === 'approved'
                    ? 'Absensi berhasil dicatat ✅'
                    : 'Absensi DITOLAK ❌ (di luar area kantor)',
                'data' => $attendance,
                'distance_meters' => round($distance, 2),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem, data absensi gagal disimpan.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function saveAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer|exists:users,id',
            'type' => 'required|integer|in:0,1,2', // 0 = check_in, 1 = check_out, 2 = check_in & check_out sekaligus
            'location_id' => 'required|integer|exists:work_locations,location_id',
            'attendance_time' => 'nullable|date|required_if:type,0,1',
            'attendance_time_in' => 'nullable|date|required_if:type,2',
            'attendance_time_out' => 'nullable|date|required_if:type,2|after:attendance_time_in',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $saved = DB::transaction(function () use ($request) {
                switch ((int) $request->type) {
                    case 0:
                        return $this->storeCheckIn($request);
                    case 1:
                        return $this->storeCheckOut($request);
                    case 2:
                        return $this->storeCheckInOut($request);
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil disimpan',
            'data' => $saved,
        ]);
    }

    private function storeCheckIn(Request $request)
    {
        $time = $this->resolveTime($request->attendance_time);
        $saved = $this->repo->CreateOrUpdate(
            $this->buildAttendancePayload($request, 'check_in', $time),
            null
        );

        $this->forceAttendanceTime($saved, $time);
        return $saved;
    }


    private function forceAttendanceTime($attendance, Carbon $time): void
    {
        if (!$attendance instanceof \Illuminate\Database\Eloquent\Model) {
            return;
        }

        DB::table($attendance->getTable())
            ->where($attendance->getKeyName(), $attendance->getKey())
            ->update([
                'attendance_time' => $time->format('Y-m-d H:i:s'),
                'attendance_date' => $time->format('Y-m-d'),
            ]);

        // Sinkronkan juga instance in-memory-nya, biar response JSON ke FE benar.
        $attendance->attendance_time = $time->format('Y-m-d H:i:s');
        $attendance->attendance_date = $time->format('Y-m-d');
    }

    private function storeCheckOut(Request $request)
    {
        $time = $this->resolveTime($request->attendance_time);

        $checkIn = Attendance::where('employee_id', $request->employee_id)
            ->where('attendance_date', $time->format('Y-m-d'))
            ->where('attendance_type', 'check_in')
            ->first();

        if (!$checkIn) {
            throw new \Exception('Gagal: data check-in untuk tanggal ini tidak ditemukan');
        }

        // Parse dari raw value supaya tidak kena mutasi timezone otomatis dari cast model.
        $checkInTime = Carbon::parse($checkIn->getRawOriginal('attendance_time'), 'Asia/Jakarta');

        $diffMinutes = $checkInTime->diffInMinutes($time);
        $diffHours = round($diffMinutes / 60, 2);

        // if (env('CONFIG_LIMIT_ABSEN') && $diffHours <= 8) {
        //     throw new \Exception('Gagal: checkout pulang, kurang dari 8 jam');
        // }

        $saved = $this->repo->CreateOrUpdate(
            $this->buildAttendancePayload($request, 'check_out', $time),
            null
        );

        $this->forceAttendanceTime($saved, $time);

        if (env('CONFIG_LIMIT_ABSEN') && $diffHours > 8) {
            $this->createOvertime($saved->attendance_id, $diffMinutes);
        }

        return $saved;
    }



    private function storeCheckInOut(Request $request)
    {

        $timeIn = Carbon::parse($request->attendance_time_in, 'Asia/Jakarta');
        $timeOut = Carbon::parse($request->attendance_time_out, 'Asia/Jakarta');


        $savedIn = $this->repo->CreateOrUpdate(
            $this->buildAttendancePayload($request, 'check_in', $timeIn),
            null
        );

        $savedOut = $this->repo->CreateOrUpdate(
            $this->buildAttendancePayload($request, 'check_out', $timeOut),
            null
        );

        $this->forceAttendanceTime($savedIn, $timeIn);
        $this->forceAttendanceTime($savedOut, $timeOut);

        $diffMinutes = $timeIn->diffInMinutes($timeOut);
        $diffHours = round($diffMinutes / 60, 2);

        if (env('CONFIG_LIMIT_ABSEN') && $diffHours > 8) {
            $this->createOvertime($savedOut->attendance_id, $diffMinutes);
        }

        return $savedOut;
    }


    private function buildAttendancePayload(Request $request, string $attendanceType, Carbon $time): array
    {
        return [
            'employee_id' => (int) $request->employee_id,
            'location_id' => (int) $request->location_id,
            'submitted_latitude' => -7.51003002,
            'submitted_longitude' => 112.54197030,
            'status' => 'approved',
            'notes' => ' absen by admin ',
            'attendance_time' => $time->format('Y-m-d H:i:s'),
            'attendance_date' => $time->format('Y-m-d'),
            'attendance_type' => $attendanceType,
        ];
    }

    private function resolveTime(?string $rawTime): Carbon
    {
        return $rawTime
            ? Carbon::parse($rawTime, 'Asia/Jakarta')
            : Carbon::now('Asia/Jakarta');
    }

    private function createOvertime(int $attendanceId, int $diffMinutes): void
    {
        $selisihJam = self::formatJamMenit($diffMinutes);
        $overtimeHour = self::hitungOvertimeTitikMenit($selisihJam);

        Overtime::create([
            'attendance_id' => $attendanceId,
            'overtime_hour' => round($overtimeHour, 2),
        ]);
    }

    public function getDetailTimeAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 400);
        }

        if (!Str::contains($request->key, 'namiroh123')) {
            return $this->error('Key tidak valid.', 422);
        }

        $dateStart = Carbon::parse($request->date_start)->startOfDay();
        $dateEnd = $request->date_end
            ? Carbon::parse($request->date_end)->startOfDay()
            : $dateStart->copy();

        $dateRange = collect(CarbonPeriod::create($dateStart, $dateEnd))
            ->map(fn($date) => $date->format('Y-m-d'));

        $attendances = Attendance::with('employee', 'overtime')
            ->whereBetween('attendance_date', [
                $dateStart->toDateString(),
                $dateEnd->toDateString(),
            ])
            ->orderBy('attendance_time')
            ->get();

        $grouped = $attendances->groupBy('employee_id')->map(function ($employeeAttendances) {
            return $employeeAttendances->groupBy(
                fn($item) => Carbon::parse($item->attendance_date)->format('Y-m-d')
            );
        });

        $data = [];

        foreach ($grouped as $employeeId => $byDate) {
            $user = optional($byDate->first()->first())->employee;

            $row = [
                'user_id' => $employeeId,
                'fullname' => optional($user)->fullname ?? optional($user)->name ?? '-',
            ];

            foreach ($dateRange as $date) {
                $records = $byDate->get($date);

                $checkInRecord = $records ? $records->where('attendance_type', 'check_in')->first() : null;
                $checkOutRecord = $records ? $records->where('attendance_type', 'check_out')->last() : null;

                $ket_izin = '-';

                if (empty(optional($checkInRecord)->attendance_time) && empty(optional($checkOutRecord)->attendance_time)) {
                    $cariKet = PengajuanIzin::where('user_id', $employeeId)
                        ->where(function ($q) use ($date) {
                            $q->whereDate('tgl_mulai', '<=', $date)
                                ->where(function ($q2) use ($date) {
                                    $q2->whereDate('tgl_selesai', '>=', $date)
                                        ->orWhereNull('tgl_selesai');
                                });
                        })
                        ->first();

                    if ($cariKet) {
                        $ket_izin = $cariKet->jenis . ' - ';
                    }
                }

                // Overtime diambil per-tanggal dari record check-out (sesuaikan ke $checkInRecord kalau ternyata itu yg benar)
                $overtimeHour = optional(optional($checkOutRecord)->overtime)->overtime_hour;
                $tot_lembur = self::formatLembur($overtimeHour);

                $row[$date] = [
                    'check_in' => $checkInRecord ? Carbon::parse($checkInRecord->attendance_time)->format('Y-m-d H:i:s') : $ket_izin,
                    'check_out' => $checkOutRecord ? Carbon::parse($checkOutRecord->attendance_time)->format('Y-m-d H:i:s') : '-',
                    'tot_lembur' => $tot_lembur,
                ];
            }

            $data[] = $row;
        }

        return $this->autoResponse($data);
    }

    function formatLembur($overtimeHour): string
    {
        if ($overtimeHour === null || $overtimeHour === '') {
            return '-';
        }

        // Normalisasi ke 2 desimal dulu (jaga-jaga kalau presisi kolom 3 digit spt "0.550")
        $raw = number_format((float) $overtimeHour, 2, '.', ''); // "0.550" -> "0.55"
        [$jamStr, $menitStr] = array_pad(explode('.', $raw), 2, '00');

        $jam = (int) $jamStr;
        $menit = (int) $menitStr;

        if ($jam <= 0 && $menit <= 0) {
            return '-';
        }
        if ($jam > 0 && $menit > 0) {
            return "{$jam} jam {$menit} menit";
        }
        return $jam > 0 ? "{$jam} jam" : "{$menit} menit";
    }

    public function store2(Request $request)//with face recognation
    {
        $ips = $request->ips();
        $today = Carbon::today()->format('Y-m-d');

        // 1. Validasi Request
        $attendanceCount = Attendance::where('employee_id', $request->employee_id)
            ->where('attendance_date', $today)
            ->count();

        $validator = Validator::make($request->all(), [
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
            'location_id' => 'required', // Pastikan location_id divalidasi
            'device_id' => 'nullable|string|max:100',
            'device_model' => 'nullable|string|max:100',
            'device_brand' => 'nullable|string|max:50',
            'android_version' => 'nullable|string|max:20',
            'app_version' => 'nullable|string|max:20',
            'gps_accuracy' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:5120', // Max 5MB upload size
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->toArray(),
            ], 400); // 400 Bad Request lebih tepat daripada 500
        }

        // =================================================================
        // 2. FAIL FAST: Cek Jarak Lokasi Terlebih Dahulu
        // (Jangan buang CPU untuk proses gambar jika jarak sudah salah)
        // =================================================================
        $location = WorkLocation::findOrFail($request->location_id);
        $distance = $this->haversineDistance(
            $location->latitude,
            $location->longitude,
            $request->submitted_latitude,
            $request->submitted_longitude
        );

        if ($distance > 25) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: jarak lebih dari 25 meter.',
                'distance_meters' => round($distance, 2),
            ], 400);
        }

        $status = $distance <= $location->radius_meters ? 'approved' : 'rejected';
        $rejection_reason = $status === 'rejected' ? 'Di luar radius kantor' : null;

        // =================================================================
        // 3. FACE COMPARISON (Python)
        // =================================================================
        $cek_last_img = $this->lastImgStr($request->employee_id); // Panggil cukup 1 kali
        $livePhoto = $request->file('photo');
        $faceResult = ['result' => null, 'confidence' => null];

        if (!empty($cek_last_img)) {
            $livePhotoRealPath = $livePhoto->getRealPath();
            $tmpDir = storage_path('app/tmp/face_compare');

            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }

            $extension_from_string = pathinfo($cek_last_img, PATHINFO_EXTENSION) ?: 'jpg';
            $tmpOld = $tmpDir . '/' . uniqid('old_') . '.' . $extension_from_string;
            $tmpLive = $tmpDir . '/' . uniqid('live_') . '.' . $livePhoto->getClientOriginalExtension();

            $filename = basename($cek_last_img);
            $basePath = env('DB_USERNAME') === 'root'
                ? 'uploads/photos_absence/'
                : 'app/public/uploads/photos_absence/';

            $localOldPhotoPath = Storage::disk('public')->path($basePath . $filename);

            if (!file_exists($localOldPhotoPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto referensi tidak ditemukan di server. Absensi dibatalkan.',
                ], 500);
            }

            copy($localOldPhotoPath, $tmpOld);
            copy($livePhotoRealPath, $tmpLive);

            $scriptPath = storage_path('app/scripts/face_compare.py');
            $pythonBin = env('PYTHON_BIN', 'python3');
            $threshold = 0.6;

            $cmd = sprintf(
                '%s %s %s %s --threshold %s --json 2>&1',
                escapeshellcmd($pythonBin),
                escapeshellarg($scriptPath),
                escapeshellarg($tmpOld),
                escapeshellarg($tmpLive),
                $threshold
            );

            $output = shell_exec($cmd);
            $faceResult = json_decode($output, true);

            @unlink($tmpOld);
            @unlink($tmpLive);

            if (json_last_error() !== JSON_ERROR_NONE || $faceResult === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses pengenalan wajah. Coba lagi.',
                ], 500);
            }

            if (isset($faceResult['error']) && $faceResult['error'] !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi Wajah Gagal: ' . $faceResult['error'],
                ], 400);
            }

            if ($faceResult['result'] === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah tidak cocok dengan data referensi. Absensi ditolak. ❌',
                    'confidence' => $faceResult['confidence'] ?? null,
                ], 403);
            }
        }

        // =================================================================
        // 4. IMAGE COMPRESSION (< 100 KB & WebP)
        // =================================================================
        $format_date_no = str_replace("-", "", $today);
        $newFileName = $request->employee_id . "_" . $format_date_no . "_" . $request->attendance_type . ".webp";

        $folderPath = env('DB_USERNAME') == 'root'
            ? 'uploads/photos_absence/'
            : 'app/public/uploads/photos_absence/';
        $fullPath = $folderPath . $newFileName;

        try {
            $image = Image::make($livePhoto->getRealPath());
            $image->resize(800, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $maxSizeKB = 100;
            $quality = 85;
            $encoded = null;

            // Force encode menjadi format WEBP (Bukan ekstensi asli file)
            while (true) {
                $encoded = $image->encode('webp', $quality);
                if (strlen($encoded) / 1024 <= $maxSizeKB || $quality <= 10) {
                    break;
                }
                $quality -= 5;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses dan kompresi gambar.',
            ], 500);
        }

        // =================================================================
        // 5. DATABASE TRANSACTION (Simpan File + DB dengan Aman)
        // =================================================================
        DB::beginTransaction();

        try {
            // 5a. Upload Gambar
            $uploadSuccess = Storage::disk('public')->put($fullPath, $encoded);
            if (!$uploadSuccess) {
                throw new \Exception("Gagal menyimpan gambar ke penyimpanan server.");
            }
            $url = Storage::url($fullPath);

            // 5b. Insert ke Database
            $attendance = Attendance::create([
                'employee_id' => $request->employee_id,
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
                'photo_url' => $url, // Simpan path final gambar
                'notes' => $request->notes,
                'ip_address' => $ips[0] ?? null,
            ]);

            // Jika semua sukses, Commit (Simpan Permanen)
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $status === 'approved'
                    ? 'Absensi berhasil dicatat ✅'
                    : 'Absensi DITOLAK ❌ (di luar area kantor)',
                'data' => $attendance,
                'distance_meters' => round($distance, 2),
                'face_match' => [
                    'result' => $faceResult['result'],
                    'confidence' => $faceResult['confidence'],
                ],
                'image' => [
                    'file_name' => $newFileName,
                    'path' => $fullPath,
                    'url' => $url,
                    'size_kb' => round(strlen($encoded) / 1024, 2),
                    'quality' => $quality,
                ],
            ], 201);

        } catch (\Exception $e) {
            // Jika ada error (Gagal simpan DB / Gagal upload) -> Batalkan semua!
            DB::rollBack();

            // Hapus file gambar jika sudah terlanjur tersimpan sebelum DB gagal
            if (Storage::disk('public')->exists($fullPath)) {
                Storage::disk('public')->delete($fullPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem, data absensi gagal disimpan.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAttendanceHistory(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            // Menggunakan regex untuk memastikan format string adalah YYYY-MM atau YYYY-MM-DD
            'tanggal' => ['required', 'string', 'regex:/^\d{4}-\d{2}(-\d{2})?$/'],
            'keyword' => 'nullable|string'
        ], [
            'tanggal.regex' => 'Format tanggal harus YYYY-MM-DD (harian) atau YYYY-MM (bulanan).'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422); // Asumsi $this->error adalah custom response Anda
        }

        // 2. Inisiasi Query Builder
        $query = DB::table('view_absensi_karyawan_v2');

        $inputTanggal = $request->tanggal;

        if (strlen($inputTanggal) === 7) {
            // Jika formatnya "YYYY-MM" (panjang string 7 karakter)
            $tahun = substr($inputTanggal, 0, 4); // Ambil 4 karakter pertama
            $bulan = substr($inputTanggal, 5, 2); // Ambil 2 karakter terakhir

            $query->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan);
        } else {
            // Jika formatnya "YYYY-MM-DD" (panjang string 10 karakter)
            $query->whereDate('tanggal', $inputTanggal);
        }

        if ($request->data_user->role !== 'HRD') {
            // Pastikan kolomnya 'employee_id', bukan 'employe_id'
            $query->where('employee_id', $request->employee_id);
        }

        // 5. Fitur Pencarian berdasarkan Keyword (fullname)
        if ($request->filled('keyword')) {
            $query->where('fullname', 'like', '%' . $request->keyword . '%');
        }

        // 6. Eksekusi Query dan simpan hasilnya
        // Tambahkan pagination jika data dirasa akan banyak, atau gunakan ->get()
        $data = $query->orderBy('tanggal', 'desc')->get();

        // 7. Kembalikan Response (Asumsi autoReponse menerima format Collection/Array)
        return $this->autoResponse($data);
    }

    public function GetDetailAbsenUserId(Request $request, $iduser)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        if (!Str::contains($request->key, 'namiroh123')) {
            return $this->error('Key tidak valid.', 422);
        }
        $getData = $this->repo->WhereDataWith(['workLocation', 'employee'], ['employee_id' => $iduser])->orderBy('attendance_time', 'desc')->get();
        return $this->autoResponse($getData);
    }


    public function getAllAttendance(Request $request)
    {
        $query = DB::table('view_absensi_karyawan');

        if ($request->data_user->role !== 'HRD') {
            return response()->json([
                'errors' => "tidak bisa akses anda bukan hrd"
            ], 422);
        }

        if ($request->filled('date')) {
            $query->where('tanggal', $request->date);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        // Search fullname (opsional)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('fullname', 'LIKE', "%{$search}%");
        }

        // Limit / Jumlah data per halaman
        $perPage = (int) $request->input('limit', 15); // default 15
        if ($perPage < 1)
            $perPage = 15;

        // Pagination
        $data = $query->paginate($perPage);

        // Response JSON rapi
        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil diambil',
            'data' => $data->items(),           // data per halaman
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ]
        ], 200);
    }


    public function getLastImageByUser(Request $request)
    {

        $type = $request->input('type', 'check_in'); // tetap support manual type jika diperlukan
        $userId = $request->employee_id;

        if (empty($userId)) {
            return response()->json(['url_image' => null]);
        }

        $isLocal = env('DB_USERNAME') === 'root';

        // Base path yang benar
        $basePath = $isLocal
            ? 'uploads/photos_absence/'           // LOCAL (sesuai screenshot)
            : 'app/public/uploads/photos_absence/'; // SERVER

        $disk = Storage::disk('public');

        try {
            $files = $disk->files($basePath);
        } catch (\Exception $e) {
            return response()->json(['url_image' => null]);
        }

        // Ambil semua file yang sesuai userId (baik check_in maupun check_out)
        $matchingFiles = collect($files)->filter(function ($filePath) use ($userId) {
            $filename = basename($filePath);
            return str_starts_with($filename, $userId . '_') &&
                (str_contains($filename, '_check_in.') || str_contains($filename, '_check_out.'));
        });

        if ($matchingFiles->isEmpty()) {
            return response()->json(['url_image' => null]);
        }

        // Sort descending berdasarkan nama file (tanggal + urutan paling baru di atas)
        $sortedFiles = $matchingFiles->sortByDesc(function ($filePath) {
            return basename($filePath);
        })->values();

        // Ambil file terakhir (paling baru)
        $latestFileName = basename($sortedFiles->first());

        // Jika ada check_out di antara file-file tersebut, prioritaskan check_out
        $checkOutFile = $sortedFiles->first(function ($filePath) {
            return str_contains(basename($filePath), '_check_out.');
        });

        $finalFile = $checkOutFile ?? $sortedFiles->first();

        if (!$finalFile) {
            return response()->json(['url_image' => null]);
        }

        $fullRelativePath = $basePath . basename($finalFile);

        // Generate URL yang benar
        $urlImage = Storage::url($fullRelativePath);

        return response()->json([
            'url_image' => $urlImage,
            'filename' => basename($finalFile),
            'type' => str_contains(basename($finalFile), '_check_out.') ? 'check_out' : 'check_in'
        ]);

    }


    public function lastImgStr($empId)
    {

        $userId = $empId;
        if (empty($userId)) {
            return response()->json(['url_image' => null]);
        }
        $isLocal = env('DB_USERNAME') === 'root';
        $basePath = $isLocal
            ? 'uploads/photos_absence/'           // LOCAL (sesuai screenshot)
            : 'app/public/uploads/photos_absence/'; // SERVER

        $disk = Storage::disk('public');

        try {
            $files = $disk->files($basePath);
        } catch (\Exception $e) {
            return null;
        }

        // Ambil semua file yang sesuai userId (baik check_in maupun check_out)
        $matchingFiles = collect($files)->filter(function ($filePath) use ($userId) {
            $filename = basename($filePath);
            return str_starts_with($filename, $userId . '_') &&
                (str_contains($filename, '_check_in.') || str_contains($filename, '_check_out.'));
        });

        if ($matchingFiles->isEmpty()) {
            return null;
        }

        // Sort descending berdasarkan nama file (tanggal + urutan paling baru di atas)
        $sortedFiles = $matchingFiles->sortByDesc(function ($filePath) {
            return basename($filePath);
        })->values();

        // Ambil file terakhir (paling baru)
        $latestFileName = basename($sortedFiles->first());

        // Jika ada check_out di antara file-file tersebut, prioritaskan check_out
        $checkOutFile = $sortedFiles->first(function ($filePath) {
            return str_contains(basename($filePath), '_check_out.');
        });

        $finalFile = $checkOutFile ?? $sortedFiles->first();

        if (!$finalFile) {
            return null;
        }

        $fullRelativePath = $basePath . basename($finalFile);

        // Generate URL yang benar
        $urlImage = Storage::url($fullRelativePath);
        return url('/') . $urlImage;
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

    public function getIzinById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:pengajuan_izin,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $data = $this->repo_izin->whereData(['id' => $request->id])->first();
        return $this->autoResponse($data);
    }

    public function listIzin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'page' => 'required|integer',
            'keyword' => 'nullable|string',
            'kolom_name' => 'required|string',
            'limit' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        //     $pass = Hash::make('isal123');
        // dd($pass);
        $where = $request->data_user->role == 'HRD' ? [] : ['user_id' => $request->employee_id];

        if ($request->keyword != null) {
            $data = $this->repo_izin->searchData($where, $request->limit, $request->page, $request->kolom_name, strtoupper($request->keyword));
        } else {
            $data = $this->repo_izin->getAllDataWithDefault($where, $request->limit, $request->page, 'created_at', 'DESC');//getDataPaginate("name",10,$request->keyword);
        }
        return $this->autoResponse($data);

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

            $folderPath = 'uploads/izin_sakit/';
            $fullPath = $folderPath . $newFileName;
            $ext = strtolower($photo->getClientOriginalExtension());
            try {
                if ($ext === 'pdf') {
                    // PDF: simpan apa adanya, jangan lewat Intervention Image
                    $newFileName = $request->employee_id . "_" . $format_date_no . "_izin.pdf";
                    $fullPath = $folderPath . $newFileName;
                    $stored = Storage::disk('public')->put($fullPath, file_get_contents($photo->getRealPath()));
                } else {
                    // Gambar: resize + compress ke webp seperti sebelumnya
                    $newFileName = $request->employee_id . "_" . $format_date_no . "_izin.webp";
                    $fullPath = $folderPath . $newFileName;

                    $image = Image::make($photo->getRealPath());
                    $image->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });

                    $maxSizeKB = 100;
                    $quality = 85;
                    do {
                        $encoded = $image->encode('webp', $quality);
                        $quality -= 5;
                    } while (strlen($encoded) / 1024 > $maxSizeKB && $quality > 10);

                    $stored = Storage::disk('public')->put($fullPath, $encoded);
                }

                if (!$stored) {
                    Log::error('Gagal menyimpan bukti_sakit', ['path' => $fullPath]);
                    throw new \Exception('Gagal menyimpan file bukti sakit ke storage.');
                }

                $validated['bukti_sakit'] = url(Storage::url($fullPath));
            } catch (\Throwable $e) {
                Log::error('Upload bukti_sakit error: ' . $e->getMessage());
                throw $e;
            }

        }

        $pengajuan = PengajuanIzin::create($validated);

        return $pengajuan;
    }
}