<?php
$sql = file_get_contents(__DIR__ . '/../database/seed.sql');

// Extract the reports VALUES block
preg_match('/INSERT INTO reports\s*\([^)]+\)\s*VALUES(.*?);/s', $sql, $m);
$block = trim($m[1] ?? '', " \n\r(");
// Split rows on line ending with ), or ),\n
$rows = preg_split('/\),\s*\n\s*--[^\n]*\n\s*\(|\),\s*\n\s*\(/s', $block);

$errors = [];
foreach ($rows as $i => $row) {
    $row = trim($row, " \n\r()");
    // Count top-level commas (not inside quotes)
    $count = 1;
    $inQ = false;
    $len = strlen($row);
    for ($j = 0; $j < $len; $j++) {
        $c = $row[$j];
        if ($c === "'" && ($j === 0 || $row[$j - 1] !== '\\')) {
            $inQ = !$inQ;
        } elseif ($c === ',' && !$inQ) {
            $count++;
        }
    }
    if ($count !== 23) {
        $errors[] = 'Row ' . ($i + 1) . ": $count values";
    }
}

if (empty($errors)) {
    echo 'OK – all ' . count($rows) . " report rows have exactly 23 values\n";
} else {
    echo "ERRORS:\n" . implode("\n", $errors) . "\n";
    echo 'Total rows checked: ' . count($rows) . "\n";
}
