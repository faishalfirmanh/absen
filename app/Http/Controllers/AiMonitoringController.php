<?php

namespace App\Http\Controllers;

use App\Services\AiMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AiMonitoringController extends Controller
{
    public function analyze(Request $request, AiMonitoringService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:' . ((int) config('ai_monitoring.max_file_mb') * 1024),
                'mimes:csv,txt,xlsx,xls',
            ],
        ], [
            'file.required' => 'File CSV atau Excel wajib diupload.',
            'file.file' => 'Input file tidak valid.',
            'file.max' => 'Ukuran file terlalu besar.',
            'file.mimes' => 'Format file harus CSV, XLS, XLSX.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi file gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if (!$request->file('file')->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload file gagal atau file rusak.',
                ], 422);
            }

            $result = $service->analyze($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Analisis selesai.',
                'data' => $result,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Analisis gagal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
