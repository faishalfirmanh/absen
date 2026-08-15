<?php

namespace App\Http\Controllers\Api;

use App\Http\Repository\JamaahVRepo;
use App\Imports\JamaahVImport;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
class JamaahVaksinController extends Controller
{
    use ApiResponse;


    protected $repo;

    public function __construct(JamaahVRepo $repo)
    {
        $this->repo = $repo;
    }


    public function uploadV(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,ods|max:5120',
        ], [
            'file.required' => 'File excel wajib diupload.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        if ($validator->fails()) {
            return $this->error('Validasi gagal.', 422, $validator->errors());
        }

        try {
            $import = new JamaahVImport();
            Excel::import($import, $request->file('file'));
            $rows = $import->rows;

            if (!$rows || $rows->isEmpty()) {
                return $this->error('File excel kosong atau header tidak sesuai template.', 422);
            }

            $failedRows = [];
            $successCount = 0;

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $rowValidator = Validator::make($row->toArray(), [
                    'name_jamaah' => 'required|string|max:255',
                    'passport_no' => 'required|string|max:255',
                    'code_vaksin' => 'nullable|string|max:255',
                    'tipe_v1' => 'nullable|string|max:255',
                    'tipe_v2' => 'nullable|string|max:255',
                    'vendor_v1' => 'nullable|string|max:255',
                    'vendor_v2' => 'nullable|string|max:255',
                    'location_v1' => 'nullable|string|max:255',
                    'location_v2' => 'nullable|string|max:255',
                    // tanggal sengaja tidak divalidasi dengan rule 'date' di sini,
                    // karena divalidasi manual lewat parseExcelDate() di bawah
                ]);

                if ($rowValidator->fails()) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'errors' => $rowValidator->errors()->all(),
                    ];
                    continue;
                }

                // Parse + validasi semua kolom tanggal secara manual
                $dateFields = [
                    'date_of_birth' => 'Tanggal lahir',
                    'date_v1' => 'Tanggal vaksin 1',
                    'date_v2' => 'Tanggal vaksin 2',
                    'until_date_v1' => 'Berlaku sampai vaksin 1',
                    'until_date_v2' => 'Berlaku sampai vaksin 2',
                ];

                $parsedDates = [];
                $dateErrors = [];

                foreach ($dateFields as $field => $label) {
                    $rawValue = $row[$field] ?? null;
                    $parsed = $this->parseExcelDate($rawValue);

                    // Hanya dianggap error kalau ada isinya tapi gagal di-parse
                    if (!empty($rawValue) && $parsed === null) {
                        $dateErrors[] = "{$label} tidak valid: \"{$rawValue}\"";
                    }

                    $parsedDates[$field] = $parsed;
                }

                if (!empty($dateErrors)) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'errors' => $dateErrors,
                    ];
                    continue;
                }

                $data = [
                    'name_jamaah' => strtoupper($row['name_jamaah']),
                    'passport_no' => $row['passport_no'],
                    'v_code_generate' => $row['code_vaksin'] ?? null,
                    'date_under_name' => $this->parseExcelDate($row['date_of_birth'] ?? null),
                    'v_name_1' => $row['vendor_v1'] ?? 'BIOFARMA B20241017-1',
                    'date_v1' => $this->parseExcelDate($row['date_v1'] ?? null),
                    'valid_until_v1' => $this->parseExcelDate($row['until_date_v1'] ?? null),
                    'location_v1' => $row['location_v1'] ?? 'BIKINI BATTOM',
                    'tipe_v1' => $row['tipe_v1'] ?? 'MENINGITIS MENINGOCOCCUS',
                    'tipe_v2' => $row['tipe_v2'] ?? 'POLIO',
                    'vendor_v2' => $row['vendor_v2'] ?? 'BIOFARMA 2101224',
                    'date_v2' => $this->parseExcelDate($row['date_v2'] ?? null),
                    'valid_until_v2' => $this->parseExcelDate($row['until_date_v2'] ?? null),
                    'location_v2' => $row['location_v2'] ?? 'BIKINI BATTOM',
                    'qr_full_urlcode' => url('/') . "/" . $row['code_vaksin'],
                    'full_url_code_qr' => url('/') . "/" . $row['code_vaksin']
                ];

                $this->repo->CreateOrUpdate($data, null);
                $successCount++;
            }

            if ($successCount === 0) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Semua baris gagal divalidasi, tidak ada data yang tersimpan.',
                    'errors' => $failedRows,
                ], 422);
            }

            DB::commit();

            return $this->autoResponse($successCount);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Excel bisa mengirim tanggal sebagai serial number, DateTime object,
     * atau string biasa tergantung format cell — tangani ketiganya.
     */
    private function parseExcelDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim($value);

        // Coba format DD/MM/YYYY dulu (konvensi Indonesia), baru fallback lainnya
        $knownFormats = ['d/m/Y', 'd/m/y', 'd-m-Y', 'd-m-y', 'Y-m-d'];

        foreach ($knownFormats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return Carbon::instance($date)->format('Y-m-d');
            }
        }

        // Fallback terakhir kalau tidak cocok format manapun di atas
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}