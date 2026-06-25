<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromFile;
use Maatwebsite\Excel\Concerns\WithEvents;

/**
 * Export langsung dari file yang sudah dimodifikasi
 * (dipakai jika kita simpan dulu ke storage lalu download)
 */
class JamaahAlamatExport implements FromFile
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function file(): \SplFileObject
    {
        return new \SplFileObject($this->filePath);
    }
}