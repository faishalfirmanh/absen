<?php
$python = 'python3';
$script = __DIR__ . '/storage/app/scripts/face_compare.py';
$img1 = 'storage/app/scripts/is1.png';   // use real image paths
$img2 = 'storage/app/scripts/is2.png';

$cmd = sprintf(
    '%s %s %s %s --threshold 0.6 --json 2>&1',
    escapeshellcmd($python),
    escapeshellarg($script),
    escapeshellarg($img1),
    escapeshellarg($img2)
);

$output = shell_exec($cmd);
$result = json_decode($output, true);

echo "Raw output:\n" . $output . "\n";
echo "Result: " . ($result['result'] ? 'TRUE ✅' : 'FALSE ❌') . "\n";
echo "Distance: " . ($result['distance'] ?? 'N/A') . "\n";
