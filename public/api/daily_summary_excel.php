<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo 'Unauthorized';
    exit();
}

function xe_excel(string $s): string
{
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $s);
    return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function col_letter(int $n): string
{
    return $n < 26 ? chr(65 + $n) : chr(64 + intdiv($n, 26)) . chr(65 + ($n % 26));
}

function excel_cell(string $ref, string $val, int $style): string
{
    return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . xe_excel($val) . '</t></is></c>';
}

$date = $_GET['date'] ?? date('Y-m-d', strtotime('-1 day'));
$action = $_GET['action'] ?? 'download';
$displayDate = date('F j, Y', strtotime($date));

$sql = "SELECT report_no, subject_id, category_id, sub_category_id, severity, building, remarks, security_remarks, submitted_at
        FROM reports
        WHERE DATE(submitted_at) = ?
        ORDER BY submitted_at ASC";
$reports = db_fetch_all($sql, 's', [$date]);

$subjects_raw = db_fetch_all('SELECT id, name FROM subjects');
$subjectMap = array_column($subjects_raw, 'name', 'id');

$categories_raw = db_fetch_all('SELECT id, name FROM categories');
$categoryMap = array_column($categories_raw, 'name', 'id');

$subcats_raw = db_fetch_all('SELECT id, name FROM subcategories');
$subcatMap = array_column($subcats_raw, 'name', 'id');

$cols = ['REPORT NO', 'SUBJECT & DETAILS', 'SEVERITY', 'ENTITY', 'TIME', 'REMARKS'];
$nCols = count($cols);
$lastCol = col_letter($nCols - 1);
$centeredCols = [0, 2, 3, 4];

$stylesXml =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
    '<fonts count="6">' .
    '<font><sz val="11"/><name val="Calibri"/></font>' .
    '<font><b/><sz val="17"/><name val="Calibri"/><color rgb="FF1A2E50"/></font>' .
    '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>' .
    '<font><sz val="10"/><name val="Calibri"/></font>' .
    '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FF1A2E50"/></font>' .
    '<font><u/><sz val="10"/><name val="Calibri"/><color rgb="FF1D4ED8"/></font>' .
    '</fonts>' .
    '<fills count="7">' .
    '<fill><patternFill patternType="none"/></fill>' .
    '<fill><patternFill patternType="gray125"/></fill>' .
    '<fill><patternFill patternType="solid"><fgColor rgb="FFFFD700"/></patternFill></fill>' .
    '<fill><patternFill patternType="solid"><fgColor rgb="FF4CAF50"/></patternFill></fill>' .
    '<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/></patternFill></fill>' .
    '<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>' .
    '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/></patternFill></fill>' .
    '</fills>' .
    '<borders count="2">' .
    '<border><left/><right/><top/><bottom/><diagonal/></border>' .
    '<border>' .
    '<left style="thin"><color auto="1"/></left>' .
    '<right style="thin"><color auto="1"/></right>' .
    '<top style="thin"><color auto="1"/></top>' .
    '<bottom style="thin"><color auto="1"/></bottom>' .
    '<diagonal/>' .
    '</border>' .
    '</borders>' .
    '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
    '<cellXfs count="10">' .
    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .
    '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
    '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
    '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>' .
    '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>' .
    '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' .
    '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' .
    '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' .
    '<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' .
    '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' .
    '</cellXfs>' .
    '</styleSheet>';

$xml = '';
$rowNum = 1;

$titleRow = $rowNum;
$xml .= '<row r="' . $rowNum . '" ht="34" customHeight="1">';
$xml .= excel_cell('A' . $rowNum, 'INCIDENT SUMMARY AND DISPOSITION REPORT', 1);
$xml .= '</row>';
$rowNum++;

$subTitleRow = $rowNum;
$xml .= '<row r="' . $rowNum . '" ht="20" customHeight="1">';
$xml .= excel_cell('A' . $rowNum, 'DAILY PERFORMANCE OVERVIEW - ' . $displayDate, 7);
$xml .= '</row>';
$rowNum++;

$headerRow = $rowNum;
$xml .= '<row r="' . $rowNum . '" ht="20" customHeight="1">';
foreach ($cols as $ci => $cn) {
    $xml .= excel_cell(col_letter($ci) . $rowNum, $cn, 2);
}
$xml .= '</row>';
$rowNum++;

if (empty($reports)) {
    $xml .= '<row r="' . $rowNum . '" ht="24" customHeight="1">';
    $xml .= excel_cell('A' . $rowNum, 'No incident reports documented for this date.', 3);
    for ($ci = 1; $ci < $nCols; $ci++) {
        $xml .= excel_cell(col_letter($ci) . $rowNum, '', 3);
    }
    $xml .= '</row>';
    $rowNum++;
} else {
    foreach ($reports as $ri => $row) {
        $isAlt = $ri % 2 === 1;
        $leftStyle = $isAlt ? 4 : 3;
        $centerStyle = $isAlt ? 6 : 5;

        $sName = (string) ($subjectMap[$row['subject_id']] ?? 'N/A');
        $cName = (string) ($categoryMap[$row['category_id']] ?? 'N/A');
        $scName = (string) ($subcatMap[$row['sub_category_id']] ?? 'N/A');
        $fullText = $sName . ': ' . $cName . ' - ' . $scName;

        $reportNo = (string) ($row['report_no'] ?? '');
        $vals = [
            $reportNo,
            $fullText,
            strtoupper((string) ($row['severity'] ?? 'N/A')),
            (string) ($row['building'] ?? 'N/A'),
            !empty($row['submitted_at']) ? date('h:i A', strtotime($row['submitted_at'])) : '',
            trim((string) ($row['remarks'] ?? '')) !== ''
                ? trim((string) ($row['remarks'] ?? ''))
                : (trim((string) ($row['security_remarks'] ?? '')) !== ''
                    ? trim((string) ($row['security_remarks'] ?? ''))
                    : 'N/A'),
        ];

        $xml .= '<row r="' . $rowNum . '" ht="28" customHeight="1">';
        foreach ($vals as $ci => $v) {
            if ($ci === 1 || $ci === 5) {
                $style = $leftStyle;
            } elseif (in_array($ci, $centeredCols, true)) {
                $style = $centerStyle;
            } else {
                $style = $leftStyle;
            }
            $xml .= excel_cell(col_letter($ci) . $rowNum, (string) $v, $style);
        }
        $xml .= '</row>';

        $rowNum++;
    }
}

$xml .= '<row r="' . $rowNum . '"></row>';
$rowNum++;

$metaHdrRow = $rowNum;
$xml .= '<row r="' . $rowNum . '" ht="18" customHeight="1">';
$xml .= excel_cell('A' . $rowNum, 'REPORT SUMMARY', 7);
$xml .= excel_cell('B' . $rowNum, '', 7);
$xml .= '</row>';
$rowNum++;

$metaRows = [
    ['Generated', date('F d, Y H:i:s')],
    ['Coverage Date', $displayDate],
    ['Total Reports', (string) count($reports)],
    ['Format', 'Daily Summary XLSX'],
];
foreach ($metaRows as $mRow) {
    $xml .= '<row r="' . $rowNum . '" ht="16" customHeight="1">';
    $xml .= excel_cell('A' . $rowNum, $mRow[0], 7);
    $xml .= excel_cell('B' . $rowNum, $mRow[1], 8);
    $xml .= '</row>';
    $rowNum++;
}

$colWidths = [16, 40, 14, 12, 12, 30];
$cwXml = '<cols>';
foreach ($colWidths as $ci => $w) {
    $n = $ci + 1;
    $cwXml .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1" bestFit="1"/>';
}
$cwXml .= '</cols>';

$mergeRefs = [
    'A' . $titleRow . ':' . $lastCol . $titleRow,
    'A' . $subTitleRow . ':' . $lastCol . $subTitleRow,
    'A' . $metaHdrRow . ':B' . $metaHdrRow,
];
$mergesXml = '<mergeCells count="' . count($mergeRefs) . '">';
foreach ($mergeRefs as $ref) {
    $mergesXml .= '<mergeCell ref="' . $ref . '"/>';
}
$mergesXml .= '</mergeCells>';

$sheetXml =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"' .
    ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
    '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/>' .
    '<selection pane="bottomLeft" activeCell="A4" sqref="A4"/></sheetView></sheetViews>' .
    $cwXml .
    '<sheetData>' .
    $xml .
    '</sheetData>' .
    $mergesXml .
    '</worksheet>';

$tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'ZipArchive unavailable on this server';
    exit();
}

$zip->addFromString(
    '[Content_Types].xml',
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
        '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
        '</Types>',
);

$zip->addFromString(
    '_rels/.rels',
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '</Relationships>',
);

$zip->addFromString(
    'xl/workbook.xml',
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        '<sheets><sheet name="Daily Summary" sheetId="1" r:id="rId1"/></sheets>' .
        '</workbook>',
);

$zip->addFromString(
    'xl/_rels/workbook.xml.rels',
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
        '</Relationships>',
);

$zip->addFromString('xl/styles.xml', $stylesXml);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
$zip->close();

$data = file_get_contents($tmp);
unlink($tmp);

$fileDate = date('M_d_Y', strtotime($date));
$filename = 'Incident_Summary_Report_' . $fileDate . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$disp = $action === 'download' ? 'attachment' : 'inline';
header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
header('Content-Length: ' . strlen($data));
header('Cache-Control: no-cache, no-store, must-revalidate');

echo $data;
exit();
