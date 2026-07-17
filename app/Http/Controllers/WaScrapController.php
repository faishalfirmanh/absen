<?php

namespace App\Http\Controllers;

use App\Http\Repository\UserRepository;
use App\Http\Repository\WaActivityRepository;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Validator;


class WaScrapController extends Controller
{
    //

    use ApiResponse;


    protected $repo, $repo_izin, $repo_wa, $repo_user;

    public function __construct(WaActivityRepository $repo_wa, UserRepository $repo_user)
    {
        $this->repo_wa = $repo_wa;
        $this->repo_user = $repo_user;
    }

    public function getUser(Request $request)
    {
        // Daftar role valid sesuai enum kolom `role` di table users
        $validRoles = [
            'Admin',
            'HRD',
            'Accounting',
            'Telemarketing',
            'CS',
            'Tiket',
            'Visa',
            'Dokumen',
            'Hotel',
            'Haji',
            'Perlengkapan',
            'Kasir',
            'Humas',
            'IT Support',
            'Desainer',
            'Digital Marketing',
        ];

        // Normalisasi input exclude jadi array, terima string "Kasir,Humas" atau array
        $excludeInput = $request->input('exclude');
        $excludeRoles = [];
        if (!empty($excludeInput)) {
            $excludeRoles = is_array($excludeInput) ? $excludeInput : explode(',', $excludeInput);
            $excludeRoles = array_values(array_unique(array_filter(array_map('trim', $excludeRoles))));
        }

        $validator = Validator::make(
            array_merge($request->all(), ['exclude' => $excludeRoles]),
            [
                'date' => 'nullable|date_format:Y-m-d',
                'key' => 'required|in:admin123',
                'exclude' => 'nullable|array',
                'exclude.*' => 'in:' . implode(',', $validRoles),
            ]
        );

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        if (!Str::contains($request->key, 'admin123')) {
            return $this->error('Key tidak valid.', 422);
        }

        $tgl = $request->date ?? date('Y-m-d');

        $users = $this->repo_user->WhereDataWith([
            'waActivity' => function ($q) use ($tgl) {
                $q->whereDate('waktu_scan', $tgl);
            },
            'waPlanActivity' => function ($q) use ($tgl) {
                $q->whereDate('waktu_scan', $tgl)
                    ->whereNotNull('plan_Kerja')
                    ->orderByDesc('waktu_scan');
            },
            'getIzin' => function ($q) use ($tgl) {
                $q->where(function ($sub) use ($tgl) {
                    $sub->whereDate('tgl_mulai', '<=', $tgl)
                        ->where(function ($sub2) use ($tgl) {
                            $sub2->whereDate('tgl_selesai', '>=', $tgl)
                                ->orWhereNull('tgl_selesai');
                        });
                });
            },
        ], [])
            ->when(!empty($excludeRoles), function ($query) use ($excludeRoles) {
                $query->whereNotIn('role', $excludeRoles);
            })
            ->get();

        $data = $users->map(function ($user) {
            $totalActivity = $user->waActivity->count();
            $izinnya = $user->getIzin->count();
            $planKerja = optional($user->waPlanActivity->first())->plan_Kerja;
            return [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role,
                'username_machine' => $user->username_machine,
                'is_login_device' => $user->is_login_device,
                'location' => $user->location,
                'location_name' => $user->location_name,
                'wa_activity' => $totalActivity > 0 ? (string) $totalActivity : null,
                'waPlanActivity' => $planKerja,
                'izin' => $izinnya
            ];
        });

        return $this->autoResponse($data);
    }

    public function getUserOld(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d',
            'key' => 'required|in:admin123',
            'exclude' => 'nullable|in:Admin,Desainer, '
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        if (!Str::contains($request->key, 'admin123')) {
            return $this->error('Key tidak valid.', 422);
        }

        $tgl = $request->date ?? date('Y-m-d');

        $users = $this->repo_user->WhereDataWith([
            'waActivity' => function ($q) use ($tgl) {
                $q->whereDate('waktu_scan', $tgl);
            },
            'waPlanActivity' => function ($q) use ($tgl) {
                $q->whereDate('waktu_scan', $tgl)
                    ->whereNotNull('plan_Kerja')
                    ->orderByDesc('waktu_scan'); // biar row terbaru di urutan pertama
            },
        ], [])->get();

        $data = $users->map(function ($user) {
            $totalActivity = $user->waActivity->count();
            $planKerja = optional($user->waPlanActivity->first())->plan_Kerja;
            return [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role' => $user->role,
                'username_machine' => $user->username_machine,
                'is_login_device' => $user->is_login_device,
                'location' => $user->location,
                'location_name' => $user->location_name,
                'wa_activity' => $totalActivity > 0 ? (string) $totalActivity : null,
                'waPlanActivity' => $planKerja,
            ];
        });

        return $this->autoResponse($data);
    }

    public function saveWa(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'nama_karyawan' => 'required|string',
            'waktu_scan' => 'required|date_format:Y-m-d H:i:s',
            'plan_Kerja' => 'nullable|string',
            'nama_room' => 'nullable|string',
            'payload_chat' => 'nullable|string',
            'user_id' => 'required|numeric',
            'key' => 'required|in:admin123',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $request->except(['key']);
        $saved = $this->repo_wa->CreateOrUpdate($request->all(), null);
        return $this->autoResponse($saved);
    }
}
