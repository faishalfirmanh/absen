<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class JamaahVImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * @var Collection
     */
    public $rows;

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Maatwebsite otomatis menormalkan header jadi snake_case,
     * jadi header di file Excel (name_jamaah, passport_no, dst)
     * akan langsung jadi key array di tiap baris.
     */
}