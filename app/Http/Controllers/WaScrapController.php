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
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date_format:Y-m-d',
            'key' => 'required|in:admin123',
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
