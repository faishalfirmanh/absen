<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Libur;
use App\Http\Requests\StoreLiburRequest;
use App\Http\Requests\UpdateLiburRequest;
use App\Http\Resources\LiburResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

class LiburController extends Controller
{
    /**
     * Tampilkan semua data libur (dengan pagination)
     */
    public function index()
    {
        $libur = Libur::latest()->paginate(10);

        return LiburResource::collection($libur);
    }

    /**
     * Simpan data libur baru
     */
    public function store(StoreLiburRequest $request): JsonResponse
    {
        // Data sudah otomatis tervalidasi oleh StoreLiburRequest
        $libur = Libur::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data libur berhasil ditambahkan.',
            'data' => new LiburResource($libur)
        ], 201); // 201 Created
    }

    /**
     * Tampilkan detail satu data libur
     */
    public function show(Request $request): JsonResponse
    {
        $data = Libur::find($request->id);
        if ($data) {
            return response()->json([
                'success' => true,
                'message' => 'Detail data libur.',
                'data' => $data
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Detail data libur.',
                'data' => null
            ], 404);
        }

    }

    /**
     * Update data libur
     */
    public function update(Request $request, $id): JsonResponse
    {
        $libur = Libur::find($id);

        if (!$libur) {
            return response()->json([
                'success' => false,
                'message' => 'Data libur tidak ditemukan.'
            ], 404);
        }

        // 2. Validasi
        $validator = Validator::make($request->all(), [
            'keterangan' => 'required|string|max:255',
            'date_holiday' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors() // Gunakan errors() agar lebih standar
            ], 422); // Gunakan 422 untuk kesalahan input
        }

        // 3. Update data secara massal (lebih efisien)
        $libur->update($request->only(['keterangan', 'date_holiday']));

        return response()->json([
            'success' => true,
            'message' => 'Data libur berhasil diperbarui.',
            'data' => $libur
        ], 200);
    }

    /**
     * Hapus data libur
     */
    public function destroy(Libur $libur): JsonResponse
    {
        $libur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data libur berhasil dihapus.'
        ], 200);
    }
}