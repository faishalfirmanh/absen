<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanIzinRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false; // ubah jadi false kalau mau pakai middleware auth nanti
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nama_custom' => 'required|string|max:150',
            'jenis' => 'required|in:Cuti,Izin Sakit,Izin Keperluan',
            'tgl_mulai' => 'required|date',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string',
            'bukti_sakit' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // max 5MB
            'kota_surat' => 'required|string|max:100',
            'tgl_surat' => 'required|date',
            'isi_surat_custom' => 'nullable|string',
            'ttd_user' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // max 2MB
            'divisi_custom' => 'nullable|string|max:100',
            'jabatan_custom' => 'nullable|string|max:100',
        ];
    }

    /**
     * Custom error messages (optional tapi recommended)
     */
    public function messages(): array
    {
        return [
            'jenis.in' => 'Jenis hanya boleh Cuti, Izin Sakit, atau Izin Keperluan.',
            'tgl_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}