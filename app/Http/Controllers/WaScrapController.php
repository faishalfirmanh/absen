<?php

namespace App\Http\Controllers;

use App\Http\Repository\WaActivityRepository;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

use Carbon\Carbon;
use Validator;


class WaScrapController extends Controller
{
    //

    use ApiResponse;


    protected $repo, $repo_izin, $repo_wa;

    public function __construct(WaActivityRepository $repo_wa)
    {
        $this->repo_wa = $repo_wa;
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
