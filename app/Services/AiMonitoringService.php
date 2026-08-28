<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

class AiMonitoringService
{
    public function analyze(UploadedFile $file)
    {
        $rows = $this->parseFile($file);
        if (empty($rows)) {
            throw new RuntimeException('Data CSV/Excel kosong atau header tidak dikenali.');
        }

        $employees = $this->groupEmployees($rows);
        $results = [];
        $contextCache = [];

        foreach ($employees as $employee) {
            $metrics = $this->analyzeEmployee($employee);
            $roleKey = $this->roleSlug($employee['role']);
            if (!isset($contextCache[$roleKey])) {
                $contextCache[$roleKey] = $this->loadContext($employee['role']);
            }

            $ai = $this->askGemini($employee, $metrics, $contextCache[$roleKey]);
            $results[] = [
                'employee' => [
                    'name' => $employee['name'],
                    'role' => $employee['role'],
                    'room_count' => count($employee['rooms']),
                    'message_count' => count($employee['rows']),
                ],
                'metrics' => $metrics['public'],
                'ai' => $ai,
            ];
        }

        return [
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'source_file' => $file->getClientOriginalName(),
            'total_rows' => count($rows),
            'total_employees' => count($results),
            'employees' => $results,
        ];
    }

    private function parseFile(UploadedFile $file)
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'csv' || $ext === 'txt') {
            return $this->parseCsv($file->getRealPath());
        }
        if ($ext === 'xlsx') {
            return $this->parseXlsx($file->getRealPath());
        }
        if ($ext === 'xls') {
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                throw new RuntimeException('PhpSpreadsheet belum terpasang untuk membaca file XLS. Jalankan composer require phpoffice/phpspreadsheet:^1.29');
            }
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
            $spreadsheet = $reader->load($file->getRealPath());
            return $this->spreadsheetToRows($spreadsheet);
        }
        throw new RuntimeException('Format file tidak didukung.');
    }

    private function parseXlsx($path)
    {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            throw new RuntimeException('PhpSpreadsheet belum terpasang untuk membaca XLSX. Jalankan composer require phpoffice/phpspreadsheet:^1.29');
        }
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($path);
        return $this->spreadsheetToRows($spreadsheet);
    }

    private function spreadsheetToRows($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $raw = $sheet->toArray('', true, true, true);
        if (empty($raw)) {
            return [];
        }

        $first = array_shift($raw);
        $headers = [];
        foreach ($first as $header) {
            $headers[] = $this->cleanHeader($header);
        }

        $rows = [];
        foreach ($raw as $data) {
            if (count($rows) >= config('ai_monitoring.max_rows')) {
                break;
            }
            if ($this->isEmptyRow($data)) {
                continue;
            }
            $item = [];
            foreach ($headers as $index => $header) {
                $key = array_keys($first)[$index] ?? null;
                $item[$header] = $key !== null && array_key_exists($key, $data) ? trim((string) $data[$key]) : '';
            }
            $rows[] = $this->normalizeRow($item);
        }

        return $rows;
    }

    private function parseCsv($path)
    {
        $fh = fopen($path, 'rb');
        if (!$fh) {
            throw new RuntimeException('Tidak bisa membaca CSV.');
        }

        $firstLine = fgets($fh);
        if ($firstLine === false) {
            fclose($fh);
            return [];
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = $this->detectDelimiter($firstLine);
        rewind($fh);

        $rawHeaders = fgetcsv($fh, 0, $delimiter);
        if (!$rawHeaders) {
            fclose($fh);
            return [];
        }

        $headers = array_map(function ($h) {
            return $this->cleanHeader($h);
        }, $rawHeaders);

        $rows = [];
        while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
            if (count($rows) >= config('ai_monitoring.max_rows')) {
                break;
            }
            if ($this->isEmptyRow($data)) {
                continue;
            }
            $item = [];
            foreach ($headers as $i => $header) {
                $item[$header] = isset($data[$i]) ? trim((string) $data[$i]) : '';
            }
            $rows[] = $this->normalizeRow($item);
        }
        fclose($fh);

        return $rows;
    }

    private function normalizeRow(array $r)
    {
        $aliases = [
            'role' => ['Role', 'role'],
            'name' => ['Nama Karyawan', 'nama karyawan', 'Nama', 'name'],
            'room' => ['Room / Kontak', 'Room', 'Kontak', 'contact'],
            'preview' => ['Preview Chat', 'preview chat', 'Chat', 'chat'],
            'scan_time' => ['Waktu Scan', 'waktu scan', 'Timestamp', 'Date', 'Tanggal'],
            'plan' => ['Plan', 'plan'],
            'no' => ['No', 'no'],
        ];

        $out = [];
        foreach ($aliases as $key => $names) {
            $out[$key] = '';
            foreach ($names as $name) {
                if (isset($r[$name])) {
                    $out[$key] = trim((string) $r[$name]);
                    break;
                }
            }
        }
        return $out;
    }

    private function groupEmployees(array $rows)
    {
        $grouped = [];
        foreach ($rows as $row) {
            $name = $row['name'] !== '' ? $row['name'] : 'Nama Tidak Diketahui';
            $role = $row['role'] !== '' ? $row['role'] : 'default';
            $key = strtolower($role . '|' . $name);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => $name,
                    'role' => $role,
                    'rows' => [],
                    'rooms' => [],
                ];
            }
            $grouped[$key]['rows'][] = $row;
            if ($row['room'] !== '') {
                $grouped[$key]['rooms'][$row['room']] = true;
            }
        }
        return array_values($grouped);
    }

    private function analyzeEmployee(array $employee)
    {
        $rows = $employee['rows'];
        $textParts = [];
        $timestamps = [];
        $shareCount = 0;
        $closingCount = 0;
        $ticketCount = 0;
        $hotelCount = 0;
        $invoiceTicketCount = 0;
        $invoiceHotelCount = 0;

        foreach ($rows as $row) {
            $preview = trim($row['preview']);
            $lower = function_exists('mb_strtolower') ? mb_strtolower($preview, 'UTF-8') : strtolower($preview);
            $textParts[] = sprintf('[%s] [Room: %s] %s', $row['scan_time'], $row['room'], $preview);

            $ts = $this->parseTimestamp($row['scan_time']);
            if ($ts !== null) {
                $timestamps[] = $ts;
            }

            if ($this->containsAny($lower, config('ai_monitoring.analysis.group_share_keywords'))) $shareCount++;
            if ($this->containsAny($lower, config('ai_monitoring.analysis.closing_keywords'))) $closingCount++;
            if ($this->containsAny($lower, config('ai_monitoring.analysis.ticket_keywords'))) $ticketCount++;
            if ($this->containsAny($lower, config('ai_monitoring.analysis.hotel_keywords'))) $hotelCount++;
            if ($this->containsAny($lower, config('ai_monitoring.analysis.invoice_keywords'))) {
                if ($this->containsAny($lower, config('ai_monitoring.analysis.ticket_keywords'))) {
                    $invoiceTicketCount++;
                } elseif ($this->containsAny($lower, config('ai_monitoring.analysis.hotel_keywords'))) {
                    $invoiceHotelCount++;
                }
            }
        }

        sort($timestamps);
        $gaps = [];
        for ($i = 1, $n = count($timestamps); $i < $n; $i++) {
            $gap = $timestamps[$i] - $timestamps[$i - 1];
            if ($gap >= 0 && $gap <= 86400) {
                $gaps[] = $gap;
            }
        }
        $avgGap = !empty($gaps) ? round(array_sum($gaps) / count($gaps), 1) : null;
        $medianGap = !empty($gaps) ? $this->median($gaps) : null;

        return [
            'public' => [
                'message_count' => count($rows),
                'room_count' => count($employee['rooms']),
                'group_share_keyword_hits' => $shareCount,
                'closing_keyword_hits' => $closingCount,
                'ticket_keyword_hits' => $ticketCount,
                'hotel_keyword_hits' => $hotelCount,
                'invoice_ticket_keyword_hits' => $invoiceTicketCount,
                'invoice_hotel_keyword_hits' => $invoiceHotelCount,
                'timestamp_count' => count($timestamps),
                'average_timestamp_gap_seconds' => $avgGap,
                'median_timestamp_gap_seconds' => $medianGap,
                'response_speed_note' => count($timestamps) < 2
                    ? 'Timestamp belum cukup untuk menghitung pola jeda.'
                    : 'Jeda timestamp antar baris dihitung sebagai sinyal, bukan bukti pasti response time personal.',
            ],
            'raw_text' => implode("\n", $textParts),
        ];
    }

    private function askGemini(array $employee, array $metrics, $context)
    {
        $apiKey = (string) config('ai_monitoring.gemini_api_key');
        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di file .env Laravel.');
        }

        $system = "Anda adalah AI auditor kinerja customer service/travel dari data percakapan WhatsApp. Jangan mengarang fakta. Bedakan metrik terukur dan inferensi. Jika bukti tidak cukup, gunakan score rendah/medium confidence dan jelaskan keterbatasannya. Nilai perilaku kerja berdasarkan data, bukan pribadi karyawan. Context role adalah aturan bisnis, bukan sumber fakta.";
        $prompt = "Analisis satu karyawan berikut. Gunakan skor 0-100 untuk response_speed, group_sharing, ticket_activity, hotel_activity, closing, invoice_ticket, invoice_hotel, follow_up, service_quality.\n\nKaryawan: {$employee['name']}\nRole: {$employee['role']}\nROLE CONTEXT:\n{$context}\n\nMETRIK TERUKUR:\n" . json_encode($metrics['public'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\nDATA CHAT:\n{$metrics['raw_text']}\n\nAturan penting:\n1. Closing hanya boleh disebut berdasarkan kata/kalimat yang memang ada di data.\n2. Invoice khusus ticket/hotel harus memiliki evidence yang mendukung.\n3. Jangan menganggap keyword sebagai closing otomatis; nilai konteks kalimatnya.\n4. Jangan membuat nama customer, nominal, tanggal, invoice, atau kejadian yang tidak ada.\n5. Untuk response speed, jangan menyebut angka pasti jika sender/waktu tidak cukup membuktikan siapa membalas siapa.\n6. Berikan rekomendasi praktis HRD/supervisor.\n";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'summary' => ['type' => 'STRING'],
                'overall_score' => ['type' => 'INTEGER'],
                'scores' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'response_speed' => ['type' => 'INTEGER'],
                        'group_sharing' => ['type' => 'INTEGER'],
                        'ticket_activity' => ['type' => 'INTEGER'],
                        'hotel_activity' => ['type' => 'INTEGER'],
                        'closing' => ['type' => 'INTEGER'],
                        'invoice_ticket' => ['type' => 'INTEGER'],
                        'invoice_hotel' => ['type' => 'INTEGER'],
                        'follow_up' => ['type' => 'INTEGER'],
                        'service_quality' => ['type' => 'INTEGER'],
                    ],
                    'required' => ['response_speed','group_sharing','ticket_activity','hotel_activity','closing','invoice_ticket','invoice_hotel','follow_up','service_quality'],
                ],
                'closing_evidence' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'phrase' => ['type' => 'STRING'],
                            'meaning' => ['type' => 'STRING'],
                            'source_room' => ['type' => 'STRING'],
                        ],
                        'required' => ['phrase','meaning','source_room'],
                    ],
                ],
                'invoice_evidence' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING'],
                            'phrase' => ['type' => 'STRING'],
                            'meaning' => ['type' => 'STRING'],
                            'source_room' => ['type' => 'STRING'],
                        ],
                        'required' => ['type','phrase','meaning','source_room'],
                    ],
                ],
                'strengths' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'weaknesses' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'recommendations' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'confidence' => ['type' => 'STRING'],
            ],
            'required' => ['summary','overall_score','scores','closing_evidence','invoice_evidence','strengths','weaknesses','recommendations','confidence'],
        ];

        $body = [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(config('ai_monitoring.gemini_model')) . ':generateContent';
        $headers = [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => (int) config('ai_monitoring.timeout'),
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Gemini cURL error: ' . $error);
        }
        if ($http < 200 || $http >= 300) {
            throw new RuntimeException('Gemini API HTTP ' . $http . ': ' . $response);
        }

        $payload = json_decode($response, true);
        $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($text === '') {
            throw new RuntimeException('Response Gemini kosong.');
        }
        $result = json_decode($text, true);
        if (!is_array($result)) {
            throw new RuntimeException('Response Gemini bukan JSON valid.');
        }

        return $result;
    }

    private function loadContext($role)
    {
        $dir = rtrim(config('ai_monitoring.context_path'), DIRECTORY_SEPARATOR);
        $slug = $this->roleSlug($role);
        $file = $dir . DIRECTORY_SEPARATOR . $slug . '.md';
        if (is_file($file)) {
            return (string) file_get_contents($file);
        }
        $default = $dir . DIRECTORY_SEPARATOR . 'default.md';
        return is_file($default) ? (string) file_get_contents($default) : '';
    }

    private function roleSlug($role)
    {
        $slug = strtolower(trim((string) $role));
        $slug = preg_replace('/[^a-z0-9_-]+/i', '_', $slug);
        return trim($slug, '_') ?: 'default';
    }

    private function containsAny($text, array $needles)
    {
        foreach ($needles as $needle) {
            $needle = function_exists('mb_strtolower') ? mb_strtolower($needle, 'UTF-8') : strtolower($needle);
            if ($needle !== '' && strpos($text, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private function parseTimestamp($value)
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        $ts = strtotime($value);
        return $ts === false ? null : $ts;
    }

    private function median(array $values)
    {
        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = (int) floor($count / 2);
        if ($count % 2) return $values[$middle];
        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    private function detectDelimiter($line)
    {
        $counts = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($counts);
        return (string) array_key_first($counts) ?: ',';
    }

    private function cleanHeader($header)
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
        return trim($header);
    }

    private function isEmptyRow($row)
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') return false;
        }
        return true;
    }
}
