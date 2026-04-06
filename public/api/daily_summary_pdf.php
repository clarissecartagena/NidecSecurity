<?php
ob_start();
require_once __DIR__ . '/../../includes/config.php';

if (!isAuthenticated()) {
    ob_end_clean();
    die('Unauthorized');
}

$date = $_GET['date'] ?? date('Y-m-d', strtotime('-1 day'));
$action = $_GET['action'] ?? 'view';

$sql = "SELECT report_no, subject_id, category_id, sub_category_id, severity, building, status, submitted_at
        FROM reports
        WHERE DATE(submitted_at) = ?
        ORDER BY submitted_at ASC";
$reports = db_fetch_all($sql, 's', [$date]);

$subjectsRaw = db_fetch_all('SELECT id, name FROM subjects');
$subjectMap = array_column($subjectsRaw, 'name', 'id');

$categoriesRaw = db_fetch_all('SELECT id, name FROM categories');
$categoryMap = array_column($categoriesRaw, 'name', 'id');

$subcatsRaw = db_fetch_all('SELECT id, name FROM subcategories');
$subcatMap = array_column($subcatsRaw, 'name', 'id');

$pageW = 595;
$pageH = 842;

function pdf_text($f, $s, $x, $y, $sz = 10, $c = '0 g')
{
    $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $s);
    return "BT /$f $sz Tf $c $x $y Td ($s) Tj ET\n";
}

function pdf_escape_uri(string $s): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

function pdf_absolute_url(string $path): string
{
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $path;
}

$totalReports = count($reports);
$resolved = 0;
$pending = 0;

foreach ($reports as $r) {
    if (isset($r['status']) && strtolower((string) $r['status']) === 'resolved') {
        $resolved++;
    } else {
        $pending++;
    }
}

$displayDate = date('F j, Y', strtotime($date));
$columns = [40, 110, 360, 415, 455, 500, 555];
$headerHeight = 22;

$startPage = function (bool $continued, int $pageNo) use (
    $pageW,
    $pageH,
    $displayDate,
    $totalReports,
    $resolved,
    $pending,
    $columns,
    $headerHeight,
): array {
    $content = "1 0 0 1 0 0 cm\n";

    if (!$continued) {
        $center = $pageW / 2;
        $y = $pageH - 40;

        $content .= pdf_text('F2', 'INCIDENT SUMMARY AND DISPOSITION REPORT', $center - 160, $y, 14, '0 g');
        $y -= 20;
        $content .= pdf_text('F1', 'NIDEC Philippines Corporation - Security Detachment', $center - 114, $y, 10, '0 g');
        $y -= 15;
        $content .= pdf_text(
            'F1',
            '119 Technology Avenue Special Economic Zone Laguna Technopark, Binan Laguna',
            $center - 162,
            $y,
            9,
            '0 g',
        );

        $y -= 40;
        $content .= pdf_text('F2', "DAILY PERFORMANCE OVERVIEW - $displayDate", 40, $y, 11, '0.1 g');

        $y -= 18;
        $currentX = 40;

        $content .= "0.2 0.4 0.8 rg $currentX " . ($y + 0.5) . " 8 8 re f\n";
        $content .= pdf_text('F2', "Reports Submitted: $totalReports", $currentX + 13, $y, 10, '0 g');
        $currentX += 145;

        $content .= "0.1 0.6 0.2 rg $currentX " . ($y + 0.5) . " 8 8 re f\n";
        $content .= pdf_text('F2', "Resolved: $resolved", $currentX + 13, $y, 10, '0 g');
        $currentX += 95;

        $content .= "0.9 0.7 0 rg $currentX " . ($y + 0.5) . " 8 8 re f\n";
        $content .= pdf_text('F2', "Pending: $pending", $currentX + 13, $y, 10, '0 g');

        $y -= 18;
        $summaryNote = "Today's operations resulted in $totalReports documented incidents.";
        $content .= pdf_text('F1', $summaryNote, 40, $y, 10, '0.4 g');

        $y -= 36;
    } else {
        $y = $pageH - 44;
        $y -= 16;
        $content .= pdf_text('F1', "Date: $displayDate | Page $pageNo", 40, $y, 9, '0.35 g');
        $y -= 20;
    }

    $content .= '0.2 g 40 ' . ($y - 7) . " 515 $headerHeight re f\n";
    $content .= "0 G 0.8 w\n";
    $content .= '40 ' . ($y + 15) . " 515 0 re S\n";

    foreach ($columns as $x) {
        $content .= "$x " . ($y - 7) . " 0 $headerHeight re S\n";
    }

    $content .= pdf_text('F2', 'REPORT NO', 44, $y, 9, '1 g');
    $content .= pdf_text('F2', 'SUBJECT & DETAILS', 175, $y, 9, '1 g');
    $content .= pdf_text('F2', 'SEVERITY', 368, $y, 9, '1 g');
    $content .= pdf_text('F2', 'ENTITY', 422, $y, 9, '1 g');
    $content .= pdf_text('F2', 'TIME', 463, $y, 9, '1 g');
    $content .= pdf_text('F2', 'LINK', 516, $y, 9, '1 g');

    // Keep first data row top exactly on the header bottom line (y - 7)
    $y -= 19;

    return [
        'content' => $content,
        'y' => $y,
        'annots' => [],
    ];
};

$pages = [];
$pageNo = 1;
$current = $startPage(false, $pageNo);

if (empty($reports)) {
    $fixedHeight = 250;
    $rowBottomY = $current['y'] - ($fixedHeight - 15);

    $current['content'] .= '0.98 g 40 ' . $rowBottomY . " 515 $fixedHeight re f\n";
    $centerX = 184;
    $centerY = $current['y'] - $fixedHeight / 2 + 16;
    $current['content'] .= pdf_text(
        'F1',
        'No incident reports documented for this date.',
        $centerX,
        $centerY,
        12,
        '0.4 g',
    );

    $current['content'] .= "0 G 0.8 w\n";
    $current['content'] .= "40 $rowBottomY 515 0 re S\n";
    $current['content'] .= "40 $rowBottomY 0 $fixedHeight re S\n";
    $current['content'] .= "555 $rowBottomY 0 $fixedHeight re S\n";
} else {
    foreach ($reports as $row) {
        $sName = $subjectMap[$row['subject_id']] ?? 'N/A';
        $cName = $categoryMap[$row['category_id']] ?? 'N/A';
        $scName = $subcatMap[$row['sub_category_id']] ?? 'N/A';
        $fullText = "$sName: $cName - $scName";

        $wrappedLines = explode("\n", wordwrap($fullText, 44, "\n", true));
        $lineCount = count($wrappedLines);
        $rowHeight = max(25, 10 + $lineCount * 12);

        $rowBottomY = $current['y'] - ($rowHeight - 12);
        if ($rowBottomY < 55) {
            $pages[] = $current;
            $pageNo++;
            $current = $startPage(true, $pageNo);
            $rowBottomY = $current['y'] - ($rowHeight - 12);
        }

        $current['content'] .= '0.98 g 40 ' . $rowBottomY . " 515 $rowHeight re f\n";
        $current['content'] .= pdf_text('F1', (string) ($row['report_no'] ?? ''), 44, $current['y'], 9, '0 g');
        $current['content'] .= pdf_text(
            'F2',
            strtoupper((string) ($row['severity'] ?? 'N/A')),
            368,
            $current['y'],
            8,
            '0 g',
        );
        $current['content'] .= pdf_text(
            'F1',
            substr((string) ($row['building'] ?? 'N/A'), 0, 10),
            422,
            $current['y'],
            8,
            '0 g',
        );
        $current['content'] .= pdf_text(
            'F1',
            date('h:i A', strtotime((string) ($row['submitted_at'] ?? 'now'))),
            463,
            $current['y'],
            8,
            '0 g',
        );

        $textY = $current['y'];
        foreach ($wrappedLines as $line) {
            $current['content'] .= pdf_text('F1', $line, 114, $textY, 9, '0 g');
            $textY -= 12;
        }

        $reportNo = trim((string) ($row['report_no'] ?? ''));
        if ($reportNo !== '') {
            $reportLink = pdf_absolute_url(app_url('reports.php?open_report=' . rawurlencode($reportNo)));
            $current['content'] .= pdf_text('F2', 'Open', 514, $current['y'], 8, '0 0 1 rg');
            $current['annots'][] = [
                'x1' => 505,
                'y1' => $rowBottomY + 3,
                'x2' => 552,
                'y2' => $current['y'] + 10,
                'uri' => $reportLink,
            ];
        }

        $current['content'] .= "0 G 1.2 w\n";
        $current['content'] .= "40 $rowBottomY 515 0 re S\n";
        foreach ($columns as $x) {
            $current['content'] .= "$x $rowBottomY 0 $rowHeight re S\n";
        }

        $current['y'] -= $rowHeight;
    }
}

$pages[] = $current;

$pageTotal = count($pages);
foreach ($pages as $idx => $pageData) {
    $pageLabel = 'Page ' . ($idx + 1) . ' of ' . $pageTotal;
    $pageData['content'] .= pdf_text('F1', 'Confidential - For Internal Use Only', 40, 30, 7, '0.5 g');
    $pageData['content'] .= pdf_text('F1', $pageLabel, 500, 30, 7, '0.5 g');
    $pages[$idx] = $pageData;
}

$formattedDateForFile = date('M_d_Y', strtotime($date));
$filename = 'Incident_Summary_Report_' . $formattedDateForFile . '.pdf';

$objects = [];
$objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objects[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
$objects[4] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

$nextObjId = 5;
$pageIds = [];

foreach ($pages as $pageData) {
    $pageObjId = $nextObjId++;
    $contentObjId = $nextObjId++;

    $annotRefs = [];
    foreach ($pageData['annots'] as $link) {
        $annotObjId = $nextObjId++;
        $annotRefs[] = $annotObjId . ' 0 R';

        $uriEsc = pdf_escape_uri((string) $link['uri']);
        $x1 = (float) $link['x1'];
        $y1 = (float) $link['y1'];
        $x2 = (float) $link['x2'];
        $y2 = (float) $link['y2'];

        $objects[$annotObjId] =
            $annotObjId .
            " 0 obj\n<< /Type /Annot /Subtype /Link /Rect [$x1 $y1 $x2 $y2] /Border [0 0 0] /A << /S /URI /URI ($uriEsc) >> >>\nendobj\n";
    }

    $contentStr = (string) $pageData['content'];
    $objects[$contentObjId] =
        $contentObjId .
        " 0 obj\n<< /Length " .
        strlen($contentStr) .
        " >>\nstream\n" .
        $contentStr .
        "endstream\nendobj\n";

    $annotsEntry = !empty($annotRefs) ? ' /Annots [ ' . implode(' ', $annotRefs) . ' ]' : '';
    $objects[$pageObjId] =
        $pageObjId .
        " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $contentObjId 0 R$annotsEntry >>\nendobj\n";

    $pageIds[] = $pageObjId . ' 0 R';
}

$objects[2] =
    "2 0 obj\n<< /Type /Pages /Kids [ " . implode(' ', $pageIds) . ' ] /Count ' . count($pageIds) . " >>\nendobj\n";

$pdf = "%PDF-1.4\n";
$offsets = [];
$maxObjId = max(array_keys($objects));

for ($i = 1; $i <= $maxObjId; $i++) {
    if (!isset($objects[$i])) {
        continue;
    }
    $offsets[$i] = strlen($pdf);
    $pdf .= $objects[$i];
}

$xrefPos = strlen($pdf);
$count = $maxObjId + 1;
$pdf .= "xref\n0 $count\n0000000000 65535 f \n";

for ($i = 1; $i <= $maxObjId; $i++) {
    $off = $offsets[$i] ?? 0;
    $pdf .= str_pad((string) $off, 10, '0', STR_PAD_LEFT) . ($off > 0 ? " 00000 n \n" : " 00000 f \n");
}

$pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

ob_end_clean();
header('Content-Type: application/pdf');

$disp = $action === 'download' ? 'attachment' : 'inline';
header("Content-Disposition: $disp; filename=\"$filename\"");

echo $pdf;
exit();
