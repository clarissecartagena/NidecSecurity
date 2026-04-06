<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user = getUser();
$role = (string) ($user['role'] ?? '');
$allowedRoles = ['ga_manager', 'ga_staff', 'security', 'department'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

function parse_date_ymd(string $s): ?string
{
    $s = trim($s);
    if ($s === '') {
        return null;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    if (!$dt) {
        return null;
    }
    return $dt->format('Y-m-d');
}

// ─── XLSX generator (proper Excel file with colors, merged cells, column widths) ─
function output_analytics_xlsx(array $f, array $rows, array $advanced = []): void
{
    $cols = [
        'DATE',
        'REPORT NO',
        'SUBJECT',
        'CATEGORY',
        'LOCATION',
        'SEVERITY',
        'BUILDING',
        'DEPARTMENT',
        'STATUS',
        'REMARKS',
        'DATE RESOLVED',
    ];
    $nCols = count($cols); // 11
    $lastCol = chr(ord('A') + $nCols - 1); // 'K'

    // Columns that should be CENTER aligned (0-indexed):
    // DATE=0, REPORT NO=1, CATEGORY=3, LOCATION=4, SEVERITY=5, BUILDING=6, DEPARTMENT=7, STATUS=8, DATE RESOLVED=10
    $centeredCols = [0, 1, 3, 4, 5, 6, 7, 8, 10];

    // ── XML escaper ────────────────────────────────────────────────────────────
    $xe = function (string $s): string {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    };
    $colLetter = function (int $n): string {
        return $n < 26 ? chr(65 + $n) : chr(64 + intdiv($n, 26)) . chr(65 + ($n % 26));
    };
    $cell = function (string $ref, string $val, int $s) use ($xe): string {
        return "<c r=\"{$ref}\" t=\"inlineStr\" s=\"{$s}\"><is><t>{$xe($val)}</t></is></c>";
    };
    $statusLabel = function (string $s): string {
        static $m = [
            'submitted_to_ga_manager' => 'Submitted to Manager',
            'sent_to_department' => 'Sent to Department',
            'under_department_fix' => 'Under Department Fix',
            'for_security_final_check' => 'For Final Check',
            'returned_to_department' => 'Returned to Department',
            'resolved' => 'Resolved',
        ];
        return $m[$s] ?? ucwords(str_replace('_', ' ', $s));
    };

    // ── Styles XML ─────────────────────────────────────────────────────────────
    // Style index map:
    //   xf[0]  = normal baseline
    //   xf[1]  = title row     (yellow bg, bold 18pt navy, centered, border)
    //   xf[2]  = col header    (green bg, bold 10pt white, centered, border)
    //   xf[3]  = data left     (white bg, 10pt, left, wrap, border)
    //   xf[4]  = data left alt (lightgray bg, 10pt, left, wrap, border)
    //   xf[5]  = meta-label    (lightblue bg, bold 10pt navy, left, border)
    //   xf[6]  = meta-value    (lightblue bg, 10pt, left, border)
    //   xf[7]  = meta-hdr      (navy bg, bold 10pt white, centered, border)
    //   xf[8]  = data center   (white bg, 10pt, center, border)
    //   xf[9]  = data center alt (lightgray bg, 10pt, center, border)
    $stylesXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
        '<fonts count="6">' .
        '<font><sz val="11"/><name val="Calibri"/></font>' .
        '<font><b/><sz val="18"/><name val="Calibri"/><color rgb="FF1A2E50"/></font>' .
        '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>' .
        '<font><sz val="10"/><name val="Calibri"/></font>' .
        '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FF1A2E50"/></font>' .
        '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>' .
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
        '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="0"/></xf>' .
        '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
        '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>' .
        '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="top" wrapText="1"/></xf>' .
        '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' .
        '<xf numFmtId="0" fontId="3" fillId="5" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' .
        '<xf numFmtId="0" fontId="5" fillId="6" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' .
        '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' .
        '<xf numFmtId="0" fontId="3" fillId="4" borderId="1" xfId="0" applyFill="1" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>' .
        '</cellXfs>' .
        '</styleSheet>';

    // ── Build sheet row XML ────────────────────────────────────────────────────
    $xml = '';
    $rowNum = 1;

    // Row 1: SECURITY REPORT title (yellow bg, bold 18pt, centered)
    $titleRow = $rowNum;
    $xml .= "<row r=\"{$rowNum}\" ht=\"36\" customHeight=\"1\">";
    $xml .= $cell("A{$rowNum}", 'SECURITY REPORT', 1);
    $xml .= "</row>\n";
    $rowNum++;

    // Row 2: Column headers — immediately after title, NO blank gap row
    $headerRow = $rowNum;
    $xml .= "<row r=\"{$rowNum}\" ht=\"20\" customHeight=\"1\">";
    foreach ($cols as $ci => $cn) {
        $xml .= $cell($colLetter($ci) . $rowNum, $cn, 2);
    }
    $xml .= "</row>\n";
    $rowNum++;

    // Data rows
    foreach ($rows as $ri => $r) {
        $isAlt = $ri % 2 === 1;
        $vals = [
            !empty($r['submitted_at']) ? date('M d, Y', strtotime($r['submitted_at'])) : '',
            (string) ($r['report_no'] ?? ''),
            (string) ($r['subject'] ?? ''),
            (string) ($r['category'] ?? ''),
            (string) ($r['location'] ?? ''),
            strtoupper((string) ($r['severity'] ?? '')),
            (string) ($r['building'] ?? ''),
            (string) ($r['department_name'] ?? ''),
            $statusLabel((string) ($r['status'] ?? '')),
            (string) ($r['details'] ?? ''),
            !empty($r['resolved_at']) ? date('M d, Y', strtotime($r['resolved_at'])) : '',
        ];
        $xml .= "<row r=\"{$rowNum}\" ht=\"30\" customHeight=\"1\">";
        foreach ($vals as $ci => $v) {
            if (in_array($ci, $centeredCols, true)) {
                $s = $isAlt ? 9 : 8;
            } else {
                $s = $isAlt ? 4 : 3;
            }
            $xml .= $cell($colLetter($ci) . $rowNum, $v, $s);
        }
        $xml .= "</row>\n";
        $rowNum++;
    }

    // Gap row after data
    $xml .= "<row r=\"{$rowNum}\"></row>\n";
    $rowNum++;

    // Metadata section header (navy bg, white bold, centered)
    $metaHdrRow = $rowNum;
    $xml .= "<row r=\"{$rowNum}\" ht=\"18\" customHeight=\"1\">";
    $xml .= $cell("A{$rowNum}", 'REPORT SUMMARY', 7);
    $xml .= $cell("B{$rowNum}", '', 7);
    $xml .= "</row>\n";
    $rowNum++;

    // Metadata rows
    $deptLabel = (int) ($f['department_id'] ?? 0) > 0 ? 'ID: ' . (int) $f['department_id'] : 'All Departments';
    $bldgLabel = !empty($f['building']) ? $f['building'] : 'All Buildings';
    $topDepartment = $advanced['department_stats'][0] ?? null;
    $slaOverall = $advanced['sla_stats']['overall'] ?? null;
    $resBreakdown = $advanced['resolution_breakdown']['buckets'] ?? null;

    $metaRows = [
        ['Generated', date('F d, Y  H:i:s')],
        ['Date Range', $f['start'] . ' to ' . $f['end']],
        ['Building', $bldgLabel],
        ['Department', $deptLabel],
        ['Total Records', (string) count($rows)],
    ];

    if (is_array($topDepartment)) {
        $metaRows[] = [
            'Top Department',
            (string) ($topDepartment['department'] ?? 'N/A') .
            ' (' .
            (string) ((int) ($topDepartment['total_reports'] ?? 0)) .
            ' reports)',
        ];
    }
    if (is_array($slaOverall)) {
        $metaRows[] = [
            'SLA Compliance',
            (($slaOverall['compliance_rate'] ?? null) !== null
                ? (string) $slaOverall['compliance_rate'] . '%'
                : 'N/A') .
            ' (Violations: ' .
            (string) ((int) ($slaOverall['violations'] ?? 0)) .
            ')',
        ];
    }
    if (is_array($resBreakdown)) {
        $metaRows[] = [
            'Resolution Split',
            '0-24h: ' .
            (string) ((int) ($resBreakdown['0_24_hours'] ?? 0)) .
            ', 1-3d: ' .
            (string) ((int) ($resBreakdown['1_3_days'] ?? 0)) .
            ', 3-7d: ' .
            (string) ((int) ($resBreakdown['3_7_days'] ?? 0)) .
            ', 7+d: ' .
            (string) ((int) ($resBreakdown['7_plus_days'] ?? 0)),
        ];
    }
    foreach ($metaRows as $mRow) {
        $xml .= "<row r=\"{$rowNum}\" ht=\"16\" customHeight=\"1\">";
        $xml .= $cell("A{$rowNum}", $mRow[0], 5);
        $xml .= $cell("B{$rowNum}", $mRow[1], 6);
        $xml .= "</row>\n";
        $rowNum++;
    }

    // ── Column widths: bestFit="1" for auto-fit ────────────────────────────────
    $colWidths = [14, 14, 40, 16, 30, 12, 11, 22, 26, 55, 14];
    $cwXml = '<cols>';
    foreach ($colWidths as $ci => $w) {
        $n = $ci + 1;
        $cwXml .= "<col min=\"{$n}\" max=\"{$n}\" width=\"{$w}\" customWidth=\"1\" bestFit=\"1\"/>";
    }
    $cwXml .= '</cols>';

    // ── Merge cells ────────────────────────────────────────────────────────────
    $mergesXml =
        '<mergeCells count="2">' .
        "<mergeCell ref=\"A{$titleRow}:{$lastCol}{$titleRow}\"/>" .
        "<mergeCell ref=\"A{$metaHdrRow}:B{$metaHdrRow}\"/>" .
        '</mergeCells>';

    // ── Assemble sheet XML ─────────────────────────────────────────────────────
    // IMPORTANT: OOXML strict element order inside <worksheet>:
    //   1. <sheetPr>          (optional)
    //   2. <dimension>        (optional)
    //   3. <sheetViews>       ← freeze panes go here
    //   4. <sheetFormatPr>    (optional)
    //   5. <cols>
    //   6. <sheetData>
    //   7. <mergeCells>
    //   (other optional elements after)
    $sheetXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"' .
        ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
        // 1. sheetViews with freeze pane — MUST come before <cols> and <sheetData>
        '<sheetViews>' .
        '<sheetView tabSelected="1" workbookViewId="0">' .
        //     ySplit="2" freezes the first 2 rows (title + header)
        //     topLeftCell="A3" = first scrollable cell
        //     activePane="bottomLeft" = the scrollable pane is active
        '<pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/>' .
        '<selection pane="bottomLeft" activeCell="A3" sqref="A3"/>' .
        '</sheetView>' .
        '</sheetViews>' .
        // 2. cols (column widths)
        $cwXml .
        // 3. sheetData (all rows)
        '<sheetData>' .
        $xml .
        '</sheetData>' .
        // 4. mergeCells (MUST come after sheetData)
        $mergesXml .
        '</worksheet>';

    // ── Pack XLSX (ZIP) ────────────────────────────────────────────────────────
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'ZipArchive unavailable on this server']);
        exit();
    }

    $zip->addFromString(
        '[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml"  ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml"           ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml"            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
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
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"' .
            ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Security Report" sheetId="1" r:id="rId1"/></sheets>' .
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

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="security_report_' . date('Ymd_His') . '.xlsx"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo $data;
}

// ─── PDF helper: escape a string for use inside PDF parentheses ───────────────
function _pdf_e(string $s): string
{
    $s = preg_replace('/[^\x20-\x7E]/u', '?', (string) $s);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

// ─── Professional multi-page analytics PDF generator ─────────────────────────
function output_analytics_pdf(array $f, array $kpis, array $rows, array $advanced = []): void
{
    $PW = 612.0;
    $PH = 792.0;
    $ML = 30.0;
    $MB = 30.0;
    $CW = 552.0; // PW - 2*ML

    // Colors [r, g, b] in 0..1 range
    $cYellow = [1.0, 0.843, 0.0]; // #FFD700 banner
    $cNavy = [0.102, 0.18, 0.314]; // #1A2E50 dark text
    $cGreen = [0.102, 0.361, 0.22]; // #1a5c38 table header
    $cGray = [0.957, 0.965, 0.973]; // alternate row bg
    $cWhite = [1.0, 1.0, 1.0];
    $cBlack = [0.0, 0.0, 0.0];
    $cBorder = [0.8, 0.82, 0.855]; // grid lines
    $cKpiBg = [0.937, 0.95, 0.967]; // KPI section background
    $cMuted = [0.42, 0.46, 0.52]; // muted label text

    // Column widths (must sum to 552)
    $colW = [62.0, 70.0, 153.0, 52.0, 88.0, 82.0, 45.0];
    $colN = ['DATE', 'REPORT NO', 'SUBJECT', 'SEVERITY', 'DEPARTMENT', 'STATUS', 'BLDG'];
    $colX = [];
    $xp = $ML;
    foreach ($colW as $w) {
        $colX[] = $xp;
        $xp += $w;
    }

    // Max chars per column at 8 pt Helvetica (~4.4 pt/char, 8 pt total padding)
    $colC = [];
    foreach ($colW as $w) {
        $colC[] = max(4, (int) (($w - 8) / 4.4));
    }

    // ── Drawing primitives ──────────────────────────────────────────────────────
    $rgb = function (array $c): string {
        return sprintf('%.3f %.3f %.3f', $c[0], $c[1], $c[2]);
    };
    $fillRect = function (float $x, float $y, float $w, float $h, array $c) use ($rgb): string {
        return sprintf("q %s rg %.2f %.2f %.2f %.2f re f Q\n", $rgb($c), $x, $y, $w, $h);
    };
    $strokeRect = function (float $x, float $y, float $w, float $h, array $c, float $lw = 0.4) use ($rgb): string {
        return sprintf("q %s RG %.2f w %.2f %.2f %.2f %.2f re S Q\n", $rgb($c), $lw, $x, $y, $w, $h);
    };
    $hLine = function (float $x1, float $y, float $x2, array $c, float $lw = 0.3) use ($rgb): string {
        return sprintf("q %s RG %.2f w %.2f %.2f m %.2f %.2f l S Q\n", $rgb($c), $lw, $x1, $y, $x2, $y);
    };
    $vLine = function (float $x, float $y1, float $y2, array $c, float $lw = 0.25) use ($rgb): string {
        return sprintf("q %s RG %.2f w %.2f %.2f m %.2f %.2f l S Q\n", $rgb($c), $lw, $x, $y1, $x, $y2);
    };
    $draw = function (float $x, float $y, string $font, float $sz, string $s, array $c) use ($rgb): string {
        return sprintf("q %s rg BT /%s %.1f Tf %.2f %.2f Td (%s) Tj ET Q\n", $rgb($c), $font, $sz, $x, $y, _pdf_e($s));
    };
    $trunc = function (string $s, int $maxch): string {
        $s = preg_replace('/[^\x20-\x7E]/u', '?', (string) $s);
        if (strlen($s) > $maxch) {
            return substr($s, 0, $maxch - 1) . '.';
        }
        return $s;
    };
    $statusLabel = function (string $s): string {
        static $m = [
            'submitted_to_ga_manager' => 'To Manager',
            'sent_to_department' => 'Sent to Dept',
            'under_department_fix' => 'Under Fix',
            'for_security_final_check' => 'Final Check',
            'returned_to_department' => 'Returned',
            'resolved' => 'Resolved',
        ];
        return $m[$s] ?? ucwords(str_replace('_', ' ', $s));
    };

    // ── KPI data ──────────────────────────────────────────────────────────────
    $kpiOpen =
        (int) $kpis['pending_ga_review'] + (int) $kpis['under_department_fix'] + (int) $kpis['waiting_security_check'];
    $topDepartment = $advanced['department_stats'][0] ?? null;
    $topDeptLabel = is_array($topDepartment) ? (string) ($topDepartment['department'] ?? 'N/A') : 'N/A';

    $kpiData = [
        ['Total Reports', (string) (int) $kpis['total_reports']],
        ['Open Reports', (string) $kpiOpen],
        ['Resolved', (string) (int) $kpis['resolved']],
        ['Overdue', (string) (int) $kpis['overdue_reports']],
        ['Avg Resolution', $kpis['avg_resolution_days'] !== null ? $kpis['avg_resolution_days'] . ' d' : 'N/A'],
        ['On-Time Rate', $kpis['on_time_fix_rate'] !== null ? $kpis['on_time_fix_rate'] . '%' : 'N/A'],
        ['Top Department', $topDeptLabel],
    ];

    // ── Layout constants ──────────────────────────────────────────────────────
    $BANNER_H = 56.0; // yellow header height
    $SUBBR_H = 22.0; // navy subheader height
    $KPI_H = 118.0; // KPI grid height
    $TBLHDR_H = 18.0; // table header row height
    $DATA_H = 14.0; // data row height
    $SEP = 6.0; // gap between KPI grid and table header
    $allColW = array_sum($colW); // = 552

    // ── Table-header row builder (reused for cont. pages) ─────────────────────
    $drawThdrRow = function (float $thY) use (
        $ML,
        $allColW,
        $TBLHDR_H,
        $colX,
        $colN,
        $fillRect,
        $hLine,
        $vLine,
        $draw,
        $cGreen,
        $cWhite,
        $cBorder,
    ): string {
        $s = $fillRect($ML, $thY, $allColW, $TBLHDR_H, $cGreen);
        foreach ($colN as $ci => $cn) {
            $ty = $thY + ($TBLHDR_H - 7) * 0.45;
            $s .= $draw($colX[$ci] + 4, $ty, 'F2', 7, $cn, $cWhite);
        }
        $s .= $hLine($ML, $thY + $TBLHDR_H, $ML + $allColW, $cBorder, 0.5);
        $s .= $hLine($ML, $thY, $ML + $allColW, $cBorder, 0.5);
        for ($ci = 1; $ci < count($colX); $ci++) {
            $s .= $vLine($colX[$ci], $thY, $thY + $TBLHDR_H, [0.7, 0.75, 0.8]);
        }
        return $s;
    };

    // ── Page 1: full header (banner + navy bar + KPI grid + table header) ─────
    $p1 = '';

    // Yellow banner
    $bY = $PH - $BANNER_H;
    $p1 .= $fillRect(0, $bY, $PW, $BANNER_H, $cYellow);
    $p1 .= $draw($ML, $bY + 22, 'F2', 22, 'SECURITY REPORT', $cNavy);
    $p1 .= $draw($ML, $bY + 8, 'F1', 8, 'NIDEC Co., Ltd.  |  Analytics Export', $cNavy);
    $p1 .= $draw(420, $bY + 22, 'F1', 8, 'Generated: ' . date('M d, Y'), $cNavy);

    // Navy subheader bar
    $nY = $bY - $SUBBR_H;
    $p1 .= $fillRect(0, $nY, $PW, $SUBBR_H, $cNavy);
    $deptLabel = (int) ($f['department_id'] ?? 0) > 0 ? 'Dept ID: ' . (int) $f['department_id'] : 'All Departments';
    $bldgLabel = !empty($f['building']) ? $f['building'] : 'All Buildings';
    $metaLine =
        'Date Range: ' .
        $f['start'] .
        ' to ' .
        $f['end'] .
        '   |   ' .
        $bldgLabel .
        '   |   ' .
        $deptLabel .
        (!empty($f['severity']) ? '   |   Severity: ' . strtoupper($f['severity']) : '');
    $p1 .= $draw($ML, $nY + 7, 'F1', 7.5, $metaLine, $cWhite);

    // KPI grid
    $kY = $nY - $KPI_H;
    $p1 .= $fillRect($ML, $kY, $allColW, $KPI_H, $cKpiBg);
    $boxW = ($allColW - 12.0) / 3.0;
    $boxH = ($KPI_H - 16.0) / 3.0;
    for ($ki = 0; $ki < 7; $ki++) {
        $col = $ki % 3;
        $row = (int) ($ki / 3);
        $bx = $ML + 3.0 + $col * ($boxW + 3.0);
        $by = $kY + $KPI_H - 4.0 - ($row + 1) * $boxH - $row * 2.0;
        $p1 .= $fillRect($bx, $by, $boxW, $boxH, $cWhite);
        $p1 .= $strokeRect($bx, $by, $boxW, $boxH, $cBorder, 0.4);
        $p1 .= $draw($bx + 5, $by + $boxH - 11, 'F1', 7, $kpiData[$ki][0], $cMuted);
        $p1 .= $draw($bx + 5, $by + 5, 'F2', 14, $kpiData[$ki][1], $cNavy);
    }

    // Table header
    $thY = $kY - $SEP - $TBLHDR_H;
    $p1 .= $drawThdrRow($thY);

    // ── Page management ───────────────────────────────────────────────────────
    $pageStreams = [];
    $curS = $p1;
    $curY = $thY; // bottom of the last drawn element
    $pageNum = 1;

    $buildContHdr = function (int $pnr) use (
        $PW,
        $PH,
        $ML,
        $allColW,
        $TBLHDR_H,
        $fillRect,
        $draw,
        $drawThdrRow,
        $cNavy,
        $cWhite,
    ): array {
        // returns [stream, startY]
        $s = '';
        $HH = 26.0;
        $hY = $PH - $HH;
        $s .= $fillRect(0, $hY, $PW, $HH, $cNavy);
        $s .= $draw($ML, $hY + 10, 'F2', 10, 'SECURITY REPORT', $cWhite);
        $s .= $draw($ML, $hY + 2, 'F1', 7, 'Continued — Page ' . $pnr, [0.75, 0.8, 0.9]);
        $s .= $draw(440, $hY + 10, 'F1', 8, date('M d, Y'), $cWhite);
        $thY = $hY - $TBLHDR_H;
        $s .= $drawThdrRow($thY);
        return [$s, $thY];
    };

    // ── Render data rows ──────────────────────────────────────────────────────
    foreach ($rows as $ri => $r) {
        $rowY = $curY - $DATA_H;

        // Page break?
        if ($rowY < $MB + 10) {
            $curS .= $hLine($ML, $curY, $ML + $allColW, $cBorder, 0.5);
            $curS .= $draw($ML, $MB - 6, 'F1', 7, 'NIDEC Security Reporting System   |   Page ' . $pageNum, [
                0.55,
                0.58,
                0.62,
            ]);
            $pageStreams[] = $curS;

            $pageNum++;
            [$contHdr, $contThY] = $buildContHdr($pageNum);
            $curS = $contHdr;
            $curY = $contThY;
            $rowY = $curY - $DATA_H;
        }

        $alt = $ri % 2 === 1;
        $bg = $alt ? $cGray : $cWhite;
        $curS .= $fillRect($ML, $rowY, $allColW, $DATA_H, $bg);

        $vals = [
            $trunc(!empty($r['submitted_at']) ? date('M d, Y', strtotime($r['submitted_at'])) : '—', $colC[0]),
            $trunc((string) ($r['report_no'] ?? ''), $colC[1]),
            $trunc((string) ($r['subject'] ?? ''), $colC[2]),
            $trunc(strtoupper((string) ($r['severity'] ?? '')), $colC[3]),
            $trunc((string) ($r['department_name'] ?? ''), $colC[4]),
            $trunc($statusLabel((string) ($r['status'] ?? '')), $colC[5]),
            $trunc((string) ($r['building'] ?? ''), $colC[6]),
        ];
        foreach ($vals as $ci => $val) {
            $ty = $rowY + ($DATA_H - 8) * 0.4;
            $curS .= $draw($colX[$ci] + 4, $ty, 'F1', 8, $val, $cBlack);
        }
        $curS .= $hLine($ML, $rowY, $ML + $allColW, $cBorder, 0.2);
        for ($ci = 1; $ci < count($colX); $ci++) {
            $curS .= $vLine($colX[$ci], $rowY, $rowY + $DATA_H, $cBorder, 0.2);
        }
        $curY = $rowY;
    }

    // Empty state
    if (empty($rows)) {
        $curS .= $draw($ML + 100, $curY - 30, 'F1', 10, 'No reports found for the selected filters.', [
            0.55,
            0.58,
            0.62,
        ]);
    }

    // Finalise last page
    $curS .= $hLine($ML, $curY, $ML + $allColW, $cBorder, 0.5);
    $curS .= $draw(
        $ML,
        $MB - 6,
        'F1',
        7,
        'NIDEC Security Reporting System   |   Page ' .
            $pageNum .
            '   |   Total records: ' .
            count($rows) .
            '   |   ' .
            date('Y-m-d H:i:s'),
        [0.55, 0.58, 0.62],
    );
    $pageStreams[] = $curS;

    // ── Assemble raw PDF ──────────────────────────────────────────────────────
    $objs = [];
    $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objs[2] = ''; // Pages – filled after page objects are known
    $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica      /Encoding /WinAnsiEncoding >>';
    $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

    $nextObj = 5;
    $pageObjs = [];
    foreach ($pageStreams as $stream) {
        $slen = strlen($stream);
        $cNum = $nextObj++;
        $objs[$cNum] = "<< /Length $slen >>\nstream\n$stream\nendstream";
        $pNum = $nextObj++;
        $objs[$pNum] =
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] ' .
            '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> ' .
            "/Contents $cNum 0 R >>";
        $pageObjs[] = $pNum;
    }
    $kids = implode(' 0 R ', $pageObjs) . ' 0 R';
    $objs[2] = "<< /Type /Pages /Kids [$kids] /Count " . count($pageObjs) . ' >>';

    ksort($objs);
    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objs as $num => $content) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "$num 0 obj\n$content\nendobj\n";
    }
    $xrefPos = strlen($pdf);
    $maxObj = max(array_keys($objs));
    $pdf .= "xref\n0 " . ($maxObj + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxObj; $i++) {
        $pdf .= str_pad((string) ($offsets[$i] ?? 0), 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size " . ($maxObj + 1) . " /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="security_report_' . date('Ymd_His') . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
}

function build_filters(array $get, array $user, string $role): array
{
    $allowedSeverities = ['low', 'medium', 'high', 'critical'];
    $allowedStatuses = [
        'submitted_to_ga_manager',
        'sent_to_department',
        'under_department_fix',
        'for_security_final_check',
        'returned_to_department',
        'resolved',
    ];

    $start = parse_date_ymd((string) ($get['start_date'] ?? ''));
    $end = parse_date_ymd((string) ($get['end_date'] ?? ''));

    // Default date range: last 30 days
    $today = new DateTime('now');
    if (!$end) {
        $end = $today->format('Y-m-d');
    }
    if (!$start) {
        $d = (clone $today)->modify('-29 days');
        $start = $d->format('Y-m-d');
    }

    $severity = strtolower(trim((string) ($get['severity'] ?? '')));
    if ($severity !== '' && !in_array($severity, $allowedSeverities, true)) {
        $severity = '';
    }

    $status = strtolower(trim((string) ($get['status'] ?? '')));
    $statusGroup = '';
    $statusExact = '';
    if ($status !== '') {
        if (in_array($status, ['open', 'closed', 'overdue'], true)) {
            $statusGroup = $status;
        } elseif (in_array($status, $allowedStatuses, true)) {
            $statusExact = $status;
        }
    }

    $deptParam = (int) ($get['department_id'] ?? 0);
    $userDeptId = (int) ($user['department_id'] ?? 0);

    $effectiveBuilding = get_effective_building_filter();

    $isDeptRole = $role === 'department';
    $effectiveDeptId = 0;

    if ($isDeptRole) {
        $effectiveDeptId = $userDeptId;
    } else {
        $effectiveDeptId = $deptParam;
    }

    $where = [];
    $params = [];
    $types = '';

    $where[] = 'r.submitted_at >= ?';
    $params[] = $start . ' 00:00:00';
    $types .= 's';

    $where[] = 'r.submitted_at <= ?';
    $params[] = $end . ' 23:59:59';
    $types .= 's';

    if ($effectiveBuilding) {
        if ($role === 'security') {
            // Security users see reports in their building OR reports they personally submitted
            $userId = (string) ($user['employee_no'] ?? '');
            $where[] = '(r.building = ? OR r.submitted_by = ?)';
            $params[] = $effectiveBuilding;
            $params[] = $userId;
            $types .= 'ss';
        } else {
            $where[] = 'r.building = ?';
            $params[] = $effectiveBuilding;
            $types .= 's';
        }
    }

    if ($effectiveDeptId > 0) {
        $where[] = 'r.responsible_department_id = ?';
        $params[] = $effectiveDeptId;
        $types .= 'i';
    }

    if ($severity !== '') {
        $where[] = 'r.severity = ?';
        $params[] = $severity;
        $types .= 's';
    }

    if ($statusGroup === 'open') {
        $where[] =
            "r.status IN ('submitted_to_ga_manager','sent_to_department','under_department_fix','for_security_final_check','returned_to_department')";
    } elseif ($statusGroup === 'closed') {
        $where[] = "r.status IN ('resolved','rejected')";
    } elseif ($statusGroup === 'overdue') {
        $where[] =
            "r.status IN ('under_department_fix','returned_to_department') AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date";
    } elseif ($statusExact !== '') {
        $where[] = 'r.status = ?';
        $params[] = $statusExact;
        $types .= 's';
    }

    return [
        'start' => $start,
        'end' => $end,
        'building' => $effectiveBuilding,
        'severity' => $severity,
        'status' => $statusGroup !== '' ? $statusGroup : $statusExact,
        'status_group' => $statusGroup,
        'status_exact' => $statusExact,
        'department_id' => $effectiveDeptId,
        'is_department_restricted' => $isDeptRole,
        'where_sql' => 'WHERE ' . implode(' AND ', $where),
        'params' => $params,
        'types' => $types,
    ];
}

function get_kpis(array $f): array
{
    $whereSql = $f['where_sql'];
    $params = $f['params'];
    $types = $f['types'];

    $row =
        db_fetch_one(
            "SELECT
            COUNT(*) AS total_reports,
            SUM(CASE WHEN r.status = 'submitted_to_ga_manager' THEN 1 ELSE 0 END) AS pending_ga_review,
            SUM(CASE WHEN r.status IN ('sent_to_department','under_department_fix','returned_to_department') THEN 1 ELSE 0 END) AS under_department_fix,
            SUM(CASE WHEN r.status = 'for_security_final_check' THEN 1 ELSE 0 END) AS waiting_security_check,
            SUM(CASE WHEN r.status = 'resolved' THEN 1 ELSE 0 END) AS resolved,
            SUM(CASE WHEN r.status = 'returned_to_department' THEN 1 ELSE 0 END) AS returned_reports,
            SUM(CASE WHEN r.status = 'under_department_fix' AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date THEN 1 ELSE 0 END) AS overdue_reports,
            AVG(CASE WHEN r.status = 'resolved' THEN TIMESTAMPDIFF(SECOND, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) ELSE NULL END) AS avg_resolution_seconds,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS on_time_fixed,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 1 ELSE 0 END) AS late_fixed
         FROM reports r
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         $whereSql",
            $types,
            $params,
        ) ?:
        [];

    $avgDays = null;
    if (isset($row['avg_resolution_seconds']) && $row['avg_resolution_seconds'] !== null) {
        $avgDays = round(((float) $row['avg_resolution_seconds']) / 86400, 1);
    }

    $onTime = (int) ($row['on_time_fixed'] ?? 0);
    $late = (int) ($row['late_fixed'] ?? 0);
    $rate = $onTime + $late > 0 ? round(($onTime / ($onTime + $late)) * 100, 1) : null;

    return [
        'total_reports' => (int) ($row['total_reports'] ?? 0),
        'pending_ga_review' => (int) ($row['pending_ga_review'] ?? 0),
        'under_department_fix' => (int) ($row['under_department_fix'] ?? 0),
        'waiting_security_check' => (int) ($row['waiting_security_check'] ?? 0),
        'resolved' => (int) ($row['resolved'] ?? 0),
        'returned_reports' => (int) ($row['returned_reports'] ?? 0),
        'avg_resolution_days' => $avgDays,
        'overdue_reports' => (int) ($row['overdue_reports'] ?? 0),
        'on_time_fix_rate' => $rate,
        'on_time_fixed' => $onTime,
        'late_fixed' => $late,
    ];
}

function get_severity_distribution(array $f): array
{
    $rows = db_fetch_all(
        "SELECT r.severity, COUNT(*) AS c
         FROM reports r
         {$f['where_sql']}
         GROUP BY r.severity
         ORDER BY FIELD(r.severity, 'low','medium','high','critical')",
        $f['types'],
        $f['params'],
    );

    $map = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
    foreach ($rows as $r) {
        $sev = (string) ($r['severity'] ?? '');
        if (isset($map[$sev])) {
            $map[$sev] = (int) ($r['c'] ?? 0);
        }
    }

    return [
        'labels' => ['Low', 'Medium', 'High', 'Critical'],
        'values' => [$map['low'], $map['medium'], $map['high'], $map['critical']],
    ];
}

function get_by_department(array $f, string $role, array $user): array
{
    $whereSql = $f['where_sql'];
    $params = $f['params'];
    $types = $f['types'];

    if ($role === 'department') {
        $deptId = (int) ($user['department_id'] ?? 0);
        $nameRow = db_fetch_one('SELECT name FROM departments WHERE id = ? LIMIT 1', 'i', [$deptId]);
        $deptName = $nameRow['name'] ?? 'Department';
        $countRow = db_fetch_one(
            "SELECT COUNT(*) AS c
             FROM reports r
             $whereSql",
            $types,
            $params,
        );
        return [
            'labels' => [$deptName],
            'values' => [(int) ($countRow['c'] ?? 0)],
        ];
    }

    $rows = db_fetch_all(
        "SELECT d.name AS department, COUNT(*) AS c
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         $whereSql
         GROUP BY d.id, d.name
         ORDER BY c DESC, d.name ASC",
        $types,
        $params,
    );

    $labels = [];
    $values = [];
    foreach ($rows as $r) {
        $labels[] = (string) ($r['department'] ?? 'Unassigned');
        $values[] = (int) ($r['c'] ?? 0);
    }

    return ['labels' => $labels, 'values' => $values];
}

function get_timeline_performance(array $f): array
{
    $row =
        db_fetch_one(
            "SELECT
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS fixed_on_time,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 1 ELSE 0 END) AS fixed_late,
            SUM(CASE WHEN r.status IN ('sent_to_department','under_department_fix','returned_to_department','for_security_final_check') AND r.fix_due_date IS NOT NULL THEN 1 ELSE 0 END) AS still_pending
         FROM reports r
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}",
            $f['types'],
            $f['params'],
        ) ?:
        [];

    $onTime = (int) ($row['fixed_on_time'] ?? 0);
    $late = (int) ($row['fixed_late'] ?? 0);
    $rate = $onTime + $late > 0 ? round(($onTime / ($onTime + $late)) * 100, 1) : null;

    return [
        'fixed_on_time' => $onTime,
        'fixed_late' => $late,
        'still_pending' => (int) ($row['still_pending'] ?? 0),
        'compliance_rate' => $rate,
    ];
}

function get_overdue_rows(array $f): array
{
    $whereSql = $f['where_sql'];
    $params = $f['params'];
    $types = $f['types'];

    // Add overdue condition (keep existing filters)
    $whereSql .=
        " AND r.status IN ('under_department_fix', 'returned_to_department') AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date";

    return db_fetch_all(
        "SELECT r.report_no, d.name AS department, r.fix_due_date,
                DATEDIFF(NOW(), r.fix_due_date) AS days_overdue
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         $whereSql
         ORDER BY r.fix_due_date ASC
         LIMIT 100",
        $types,
        $params,
    );
}

function get_trend(string $mode, array $fBase): array
{
    $mode = in_array($mode, ['daily', 'weekly', 'monthly'], true) ? $mode : 'daily';

    // Rebuild date window relative to today, keep other filters (dept/severity/status)
    $today = new DateTime('now');

    $where = [];
    $params = [];
    $types = '';

    // Extract non-date filters from base by reusing its effective dept/sev/status
    $deptId = (int) ($fBase['department_id'] ?? 0);
    $severity = (string) ($fBase['severity'] ?? '');
    $status = (string) ($fBase['status'] ?? '');

    if ($mode === 'daily') {
        $start = (clone $today)->modify('-6 days')->format('Y-m-d');
        $end = $today->format('Y-m-d');
        $where[] = 'r.submitted_at >= ?';
        $params[] = $start . ' 00:00:00';
        $types .= 's';
        $where[] = 'r.submitted_at <= ?';
        $params[] = $end . ' 23:59:59';
        $types .= 's';

        if ($deptId > 0) {
            $where[] = 'r.responsible_department_id = ?';
            $params[] = $deptId;
            $types .= 'i';
        }
        if ($severity !== '') {
            $where[] = 'r.severity = ?';
            $params[] = $severity;
            $types .= 's';
        }
        if ($status !== '') {
            $where[] = 'r.status = ?';
            $params[] = $status;
            $types .= 's';
        }

        $rows = db_fetch_all(
            "SELECT DATE(r.submitted_at) AS d, COUNT(*) AS c
             FROM reports r
             WHERE " .
                implode(' AND ', $where) .
                "
             GROUP BY DATE(r.submitted_at)",
            $types,
            $params,
        );

        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = (int) $r['c'];
        }

        $labels = [];
        $values = [];
        $cur = new DateTime($start);
        for ($i = 0; $i < 7; $i++) {
            $key = $cur->format('Y-m-d');
            $labels[] = $cur->format('M d');
            $values[] = (int) ($map[$key] ?? 0);
            $cur->modify('+1 day');
        }

        return ['mode' => 'daily', 'labels' => $labels, 'values' => $values];
    }

    if ($mode === 'weekly') {
        $start = (clone $today)->modify('-27 days')->format('Y-m-d');
        $end = $today->format('Y-m-d');

        $where[] = 'r.submitted_at >= ?';
        $params[] = $start . ' 00:00:00';
        $types .= 's';
        $where[] = 'r.submitted_at <= ?';
        $params[] = $end . ' 23:59:59';
        $types .= 's';

        if ($deptId > 0) {
            $where[] = 'r.responsible_department_id = ?';
            $params[] = $deptId;
            $types .= 'i';
        }
        if ($severity !== '') {
            $where[] = 'r.severity = ?';
            $params[] = $severity;
            $types .= 's';
        }
        if ($status !== '') {
            $where[] = 'r.status = ?';
            $params[] = $status;
            $types .= 's';
        }

        $rows = db_fetch_all(
            "SELECT
                DATE_SUB(DATE(r.submitted_at), INTERVAL WEEKDAY(r.submitted_at) DAY) AS week_start,
                COUNT(*) AS c
             FROM reports r
             WHERE " .
                implode(' AND ', $where) .
                "
             GROUP BY week_start
             ORDER BY week_start ASC",
            $types,
            $params,
        );

        $map = [];
        foreach ($rows as $r) {
            $map[$r['week_start']] = (int) $r['c'];
        }

        // Build last 4 week starts (Mon)
        $wkStart = (clone $today)->modify('-' . ((int) $today->format('N') - 1) . ' days');
        $wkStart->setTime(0, 0, 0);
        $wkStart->modify('-3 weeks');

        $labels = [];
        $values = [];
        for ($i = 0; $i < 4; $i++) {
            $key = $wkStart->format('Y-m-d');
            $labels[] = $wkStart->format('M d');
            $values[] = (int) ($map[$key] ?? 0);
            $wkStart->modify('+1 week');
        }

        return ['mode' => 'weekly', 'labels' => $labels, 'values' => $values];
    }

    // monthly
    $start = (clone $today)->modify('first day of this month')->modify('-11 months')->format('Y-m-d');
    $end = $today->format('Y-m-d');

    $where[] = 'r.submitted_at >= ?';
    $params[] = $start . ' 00:00:00';
    $types .= 's';
    $where[] = 'r.submitted_at <= ?';
    $params[] = $end . ' 23:59:59';
    $types .= 's';

    if ($deptId > 0) {
        $where[] = 'r.responsible_department_id = ?';
        $params[] = $deptId;
        $types .= 'i';
    }
    if ($severity !== '') {
        $where[] = 'r.severity = ?';
        $params[] = $severity;
        $types .= 's';
    }
    if ($status !== '') {
        $where[] = 'r.status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $rows = db_fetch_all(
        "SELECT DATE_FORMAT(r.submitted_at, '%Y-%m') AS ym, COUNT(*) AS c
         FROM reports r
         WHERE " .
            implode(' AND ', $where) .
            "
         GROUP BY ym
         ORDER BY ym ASC",
        $types,
        $params,
    );

    $map = [];
    foreach ($rows as $r) {
        $map[$r['ym']] = (int) $r['c'];
    }

    $labels = [];
    $values = [];
    $cur = new DateTime($start);
    for ($i = 0; $i < 12; $i++) {
        $ym = $cur->format('Y-m');
        $labels[] = $cur->format('M');
        $values[] = (int) ($map[$ym] ?? 0);
        $cur->modify('+1 month');
    }

    return ['mode' => 'monthly', 'labels' => $labels, 'values' => $values];
}

function clone_filters_with_period(array $f, string $start, string $end): array
{
    $nf = $f;
    $nf['start'] = $start;
    $nf['end'] = $end;
    $nf['params'][0] = $start . ' 00:00:00';
    $nf['params'][1] = $end . ' 23:59:59';
    return $nf;
}

function get_department_stats(array $f): array
{
    $rows = db_fetch_all(
        "SELECT
            d.id AS department_id,
            d.name AS department,
            COUNT(*) AS total_reports,
            SUM(CASE WHEN r.status IN ('under_department_fix','returned_to_department') AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date THEN 1 ELSE 0 END) AS overdue_reports,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL THEN 1 ELSE 0 END) AS sla_applicable,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS sla_on_time,
            AVG(CASE WHEN r.status = 'resolved' THEN TIMESTAMPDIFF(SECOND, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) END) AS avg_resolution_seconds
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         GROUP BY d.id, d.name
         ORDER BY total_reports DESC, d.name ASC",
        $f['types'],
        $f['params'],
    );

    $medianRows = db_fetch_all(
        "SELECT
            d.id AS department_id,
            TIMESTAMPDIFF(SECOND, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) AS resolution_seconds
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']} AND r.status = 'resolved' AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) IS NOT NULL
         ORDER BY d.id ASC, resolution_seconds ASC",
        $f['types'],
        $f['params'],
    );

    $medians = [];
    foreach ($medianRows as $r) {
        $did = (int) ($r['department_id'] ?? 0);
        if ($did <= 0) {
            continue;
        }
        if (!isset($medians[$did])) {
            $medians[$did] = [];
        }
        $medians[$did][] = (int) ($r['resolution_seconds'] ?? 0);
    }

    $result = [];
    foreach ($rows as $r) {
        $did = (int) ($r['department_id'] ?? 0);
        $vals = $medians[$did] ?? [];
        $medianSeconds = null;
        $cnt = count($vals);
        if ($cnt > 0) {
            $mid = intdiv($cnt, 2);
            if ($cnt % 2 === 0) {
                $medianSeconds = (int) round(($vals[$mid - 1] + $vals[$mid]) / 2);
            } else {
                $medianSeconds = (int) $vals[$mid];
            }
        }

        $total = (int) ($r['total_reports'] ?? 0);
        $overdue = (int) ($r['overdue_reports'] ?? 0);
        $slaApplicable = (int) ($r['sla_applicable'] ?? 0);
        $slaOnTime = (int) ($r['sla_on_time'] ?? 0);

        $result[] = [
            'department_id' => $did,
            'department' => (string) ($r['department'] ?? 'Unassigned'),
            'total_reports' => $total,
            'avg_resolution_days' =>
                $r['avg_resolution_seconds'] !== null ? round(((float) $r['avg_resolution_seconds']) / 86400, 2) : null,
            'median_resolution_days' => $medianSeconds !== null ? round($medianSeconds / 86400, 2) : null,
            'sla_compliance' => $slaApplicable > 0 ? round(($slaOnTime / $slaApplicable) * 100, 1) : null,
            'overdue_rate' => $total > 0 ? round(($overdue / $total) * 100, 1) : 0.0,
            'sla_violations' => max(0, $slaApplicable - $slaOnTime),
        ];
    }

    return $result;
}

function get_resolution_breakdown(array $f): array
{
    $overall =
        db_fetch_one(
            "SELECT
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) < 24 THEN 1 ELSE 0 END) AS b_0_24,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) >= 24 AND TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) < 72 THEN 1 ELSE 0 END) AS b_1_3,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) >= 72 AND TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) < 168 THEN 1 ELSE 0 END) AS b_3_7,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) >= 168 THEN 1 ELSE 0 END) AS b_7_plus,
                COUNT(*) AS total_resolved
             FROM reports r
             LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
             {$f['where_sql']} AND r.status = 'resolved' AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) IS NOT NULL",
            $f['types'],
            $f['params'],
        ) ?:
        [];

    $deptRows = db_fetch_all(
        "SELECT
            d.id AS department_id,
            d.name AS department,
            SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) < 24 THEN 1 ELSE 0 END) AS b_0_24,
            SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) >= 24 AND TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) < 72 THEN 1 ELSE 0 END) AS b_1_3,
            SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) >= 72 AND TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) < 168 THEN 1 ELSE 0 END) AS b_3_7,
            SUM(CASE WHEN TIMESTAMPDIFF(HOUR, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) >= 168 THEN 1 ELSE 0 END) AS b_7_plus,
            COUNT(*) AS total_resolved
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']} AND r.status = 'resolved' AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) IS NOT NULL
         GROUP BY d.id, d.name
         ORDER BY total_resolved DESC, d.name ASC",
        $f['types'],
        $f['params'],
    );

    return [
        'buckets' => [
            '0_24_hours' => (int) ($overall['b_0_24'] ?? 0),
            '1_3_days' => (int) ($overall['b_1_3'] ?? 0),
            '3_7_days' => (int) ($overall['b_3_7'] ?? 0),
            '7_plus_days' => (int) ($overall['b_7_plus'] ?? 0),
        ],
        'total_resolved' => (int) ($overall['total_resolved'] ?? 0),
        'by_department' => array_map(static function (array $r): array {
            return [
                'department_id' => (int) ($r['department_id'] ?? 0),
                'department' => (string) ($r['department'] ?? 'Unassigned'),
                'total_resolved' => (int) ($r['total_resolved'] ?? 0),
                '0_24_hours' => (int) ($r['b_0_24'] ?? 0),
                '1_3_days' => (int) ($r['b_1_3'] ?? 0),
                '3_7_days' => (int) ($r['b_3_7'] ?? 0),
                '7_plus_days' => (int) ($r['b_7_plus'] ?? 0),
            ];
        }, $deptRows),
    ];
}

function get_sla_stats(array $f): array
{
    $overall =
        db_fetch_one(
            "SELECT
                SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL THEN 1 ELSE 0 END) AS applicable,
                SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS on_time,
                SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 1 ELSE 0 END) AS violations
             FROM reports r
             LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
             {$f['where_sql']}",
            $f['types'],
            $f['params'],
        ) ?:
        [];

    $deptRows = db_fetch_all(
        "SELECT
            d.id AS department_id,
            d.name AS department,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL THEN 1 ELSE 0 END) AS applicable,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS on_time,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 1 ELSE 0 END) AS violations
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         GROUP BY d.id, d.name",
        $f['types'],
        $f['params'],
    );

    $perDept = [];
    foreach ($deptRows as $r) {
        $applicable = (int) ($r['applicable'] ?? 0);
        $onTime = (int) ($r['on_time'] ?? 0);
        $violations = (int) ($r['violations'] ?? 0);
        $perDept[] = [
            'department_id' => (int) ($r['department_id'] ?? 0),
            'department' => (string) ($r['department'] ?? 'Unassigned'),
            'sla_applicable' => $applicable,
            'on_time' => $onTime,
            'violations' => $violations,
            'compliance_rate' => $applicable > 0 ? round(($onTime / $applicable) * 100, 1) : null,
        ];
    }

    usort($perDept, static function (array $a, array $b): int {
        $ra = $a['compliance_rate'] === null ? 101.0 : (float) $a['compliance_rate'];
        $rb = $b['compliance_rate'] === null ? 101.0 : (float) $b['compliance_rate'];
        if ($ra === $rb) {
            return $b['violations'] <=> $a['violations'];
        }
        return $ra <=> $rb;
    });

    $applicableOverall = (int) ($overall['applicable'] ?? 0);
    $onTimeOverall = (int) ($overall['on_time'] ?? 0);
    $violationsOverall = (int) ($overall['violations'] ?? 0);

    return [
        'overall' => [
            'applicable' => $applicableOverall,
            'on_time' => $onTimeOverall,
            'violations' => $violationsOverall,
            'compliance_rate' => $applicableOverall > 0 ? round(($onTimeOverall / $applicableOverall) * 100, 1) : null,
        ],
        'per_department' => $perDept,
        'worst_departments' => array_values(
            array_filter(
                array_slice($perDept, 0, 5),
                static fn(array $r): bool => (int) ($r['sla_applicable'] ?? 0) > 0,
            ),
        ),
    ];
}

function get_building_stats(array $f): array
{
    $rows = db_fetch_all(
        "SELECT
            r.building,
            COUNT(*) AS total_reports,
            AVG(CASE WHEN r.status = 'resolved' THEN TIMESTAMPDIFF(SECOND, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) END) AS avg_resolution_seconds,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL THEN 1 ELSE 0 END) AS sla_applicable,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS sla_on_time
         FROM reports r
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         GROUP BY r.building
         ORDER BY total_reports DESC, r.building ASC",
        $f['types'],
        $f['params'],
    );

    return array_map(static function (array $r): array {
        $applicable = (int) ($r['sla_applicable'] ?? 0);
        $onTime = (int) ($r['sla_on_time'] ?? 0);
        return [
            'building' => (string) ($r['building'] ?? 'N/A'),
            'total_reports' => (int) ($r['total_reports'] ?? 0),
            'avg_resolution_days' =>
                $r['avg_resolution_seconds'] !== null ? round(((float) $r['avg_resolution_seconds']) / 86400, 2) : null,
            'sla_compliance' => $applicable > 0 ? round(($onTime / $applicable) * 100, 1) : null,
        ];
    }, $rows);
}

function get_recurring_issues(array $f): array
{
    $overall =
        db_fetch_one(
            "SELECT
                COUNT(*) AS total_reports,
                SUM(CASE WHEN r.reopen_count > 0 THEN 1 ELSE 0 END) AS reopened_reports,
                SUM(r.reopen_count) AS reopen_events
             FROM reports r
             {$f['where_sql']}",
            $f['types'],
            $f['params'],
        ) ?:
        [];

    $topCategories = db_fetch_all(
        "SELECT
            r.category,
            COUNT(*) AS affected_reports,
            SUM(r.reopen_count) AS reopen_events
         FROM reports r
         {$f['where_sql']} AND r.reopen_count > 0
         GROUP BY r.category
         ORDER BY reopen_events DESC, affected_reports DESC, r.category ASC
         LIMIT 10",
        $f['types'],
        $f['params'],
    );

    $total = (int) ($overall['total_reports'] ?? 0);
    $reopened = (int) ($overall['reopened_reports'] ?? 0);

    return [
        'reopened_reports' => $reopened,
        'reopen_events' => (int) ($overall['reopen_events'] ?? 0),
        'reopened_rate' => $total > 0 ? round(($reopened / $total) * 100, 1) : 0.0,
        'top_categories' => array_map(static function (array $r): array {
            return [
                'category' => (string) ($r['category'] ?? 'Uncategorized'),
                'affected_reports' => (int) ($r['affected_reports'] ?? 0),
                'reopen_events' => (int) ($r['reopen_events'] ?? 0),
            ];
        }, $topCategories),
    ];
}

function get_detailed_rows(array $f): array
{
    $rows = db_fetch_all(
        "SELECT
            r.report_no,
            r.subject,
            r.category,
            r.location,
            r.severity,
            r.status,
            r.building,
            d.name AS department,
            r.submitted_at,
            r.fix_due_date,
            COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) AS resolved_at,
            CASE
                WHEN COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) IS NULL THEN NULL
                ELSE TIMESTAMPDIFF(SECOND, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at))
            END AS resolution_seconds,
            CASE
                WHEN r.fix_due_date IS NULL THEN 'n/a'
                WHEN COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 'on_time'
                WHEN COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 'late'
                WHEN r.status IN ('under_department_fix','returned_to_department') AND NOW() > r.fix_due_date THEN 'overdue'
                ELSE 'pending'
            END AS sla_status
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         ORDER BY r.submitted_at DESC
         LIMIT 500",
        $f['types'],
        $f['params'],
    );

    return array_map(static function (array $r): array {
        $sec = $r['resolution_seconds'] !== null ? (int) $r['resolution_seconds'] : null;
        return [
            'report_no' => (string) ($r['report_no'] ?? ''),
            'subject' => (string) ($r['subject'] ?? ''),
            'category' => (string) ($r['category'] ?? ''),
            'location' => (string) ($r['location'] ?? ''),
            'severity' => (string) ($r['severity'] ?? ''),
            'status' => (string) ($r['status'] ?? ''),
            'building' => (string) ($r['building'] ?? ''),
            'department' => (string) ($r['department'] ?? ''),
            'submitted_at' => (string) ($r['submitted_at'] ?? ''),
            'resolved_at' => (string) ($r['resolved_at'] ?? ''),
            'resolution_hours' => $sec !== null ? round($sec / 3600, 1) : null,
            'resolution_days' => $sec !== null ? round($sec / 86400, 2) : null,
            'sla_status' => (string) ($r['sla_status'] ?? 'n/a'),
        ];
    }, $rows);
}

function get_period_summary(array $f, string $start, string $end): array
{
    $pf = clone_filters_with_period($f, $start, $end);

    $overall =
        db_fetch_one(
            "SELECT
                COUNT(*) AS total_reports,
                SUM(CASE WHEN r.status IN ('resolved','rejected') THEN 1 ELSE 0 END) AS closed_reports,
                SUM(CASE WHEN r.status IN ('under_department_fix','returned_to_department') AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date THEN 1 ELSE 0 END) AS overdue_reports
             FROM reports r
             {$pf['where_sql']}",
            $pf['types'],
            $pf['params'],
        ) ?:
        [];

    $dept = db_fetch_all(
        "SELECT d.id AS department_id, d.name AS department, COUNT(*) AS total_reports
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         {$pf['where_sql']}
         GROUP BY d.id, d.name",
        $pf['types'],
        $pf['params'],
    );

    return [
        'start' => $start,
        'end' => $end,
        'total_reports' => (int) ($overall['total_reports'] ?? 0),
        'closed_reports' => (int) ($overall['closed_reports'] ?? 0),
        'overdue_reports' => (int) ($overall['overdue_reports'] ?? 0),
        'by_department' => $dept,
    ];
}

function get_comparison_data(array $f): array
{
    $start = new DateTime($f['start']);
    $end = new DateTime($f['end']);
    $days = (int) $start->diff($end)->format('%a') + 1;

    $prevEnd = (clone $start)->modify('-1 day');
    $prevStart = (clone $prevEnd)->modify('-' . max(0, $days - 1) . ' days');

    $current = get_period_summary($f, $f['start'], $f['end']);
    $previous = get_period_summary($f, $prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d'));

    $pct = static function (int $cur, int $prev): ?float {
        if ($prev <= 0) {
            return $cur > 0 ? 100.0 : 0.0;
        }
        return round((($cur - $prev) / $prev) * 100, 1);
    };

    $curDeptMap = [];
    foreach ($current['by_department'] ?? [] as $r) {
        $did = (int) ($r['department_id'] ?? 0);
        if ($did <= 0) {
            continue;
        }
        $curDeptMap[$did] = [
            'department' => (string) ($r['department'] ?? 'Unassigned'),
            'current_total' => (int) ($r['total_reports'] ?? 0),
        ];
    }

    $prevDeptMap = [];
    foreach ($previous['by_department'] ?? [] as $r) {
        $did = (int) ($r['department_id'] ?? 0);
        if ($did <= 0) {
            continue;
        }
        $prevDeptMap[$did] = (int) ($r['total_reports'] ?? 0);
    }

    $deptCompare = [];
    foreach ($curDeptMap as $did => $item) {
        $prev = (int) ($prevDeptMap[$did] ?? 0);
        $cur = (int) ($item['current_total'] ?? 0);
        $deptCompare[] = [
            'department_id' => $did,
            'department' => $item['department'],
            'current_total' => $cur,
            'previous_total' => $prev,
            'delta' => $cur - $prev,
            'delta_pct' => $pct($cur, $prev),
        ];
    }

    usort($deptCompare, static fn(array $a, array $b): int => $b['current_total'] <=> $a['current_total']);

    return [
        'current_period' => [
            'start' => $current['start'],
            'end' => $current['end'],
            'total_reports' => (int) ($current['total_reports'] ?? 0),
            'closed_reports' => (int) ($current['closed_reports'] ?? 0),
            'overdue_reports' => (int) ($current['overdue_reports'] ?? 0),
        ],
        'previous_period' => [
            'start' => $previous['start'],
            'end' => $previous['end'],
            'total_reports' => (int) ($previous['total_reports'] ?? 0),
            'closed_reports' => (int) ($previous['closed_reports'] ?? 0),
            'overdue_reports' => (int) ($previous['overdue_reports'] ?? 0),
        ],
        'deltas' => [
            'total_reports_pct' => $pct(
                (int) ($current['total_reports'] ?? 0),
                (int) ($previous['total_reports'] ?? 0),
            ),
            'closed_reports_pct' => $pct(
                (int) ($current['closed_reports'] ?? 0),
                (int) ($previous['closed_reports'] ?? 0),
            ),
            'overdue_reports_pct' => $pct(
                (int) ($current['overdue_reports'] ?? 0),
                (int) ($previous['overdue_reports'] ?? 0),
            ),
        ],
        'department_vs_department' => $deptCompare,
    ];
}

$f = build_filters($_GET, $user, $role);

$tab = trim((string) ($_GET['tab'] ?? 'metrics'));
$allowedTabs = ['metrics', 'trends', 'departmental', 'incident', 'records'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'metrics';
}

$departmentStats = get_department_stats($f);
$resolutionBreakdown = get_resolution_breakdown($f);
$slaStats = get_sla_stats($f);
$buildingStats = get_building_stats($f);
$comparisonData = get_comparison_data($f);
$recurringIssues = get_recurring_issues($f);
$detailedRows = get_detailed_rows($f);

$advancedSummary = [
    'department_stats' => $departmentStats,
    'resolution_breakdown' => $resolutionBreakdown,
    'sla_stats' => $slaStats,
    'building_stats' => $buildingStats,
    'comparison_data' => $comparisonData,
    'recurring_issues' => $recurringIssues,
];

$export = trim((string) ($_GET['export'] ?? ''));
if ($export === 'csv' || $export === 'xlsx') {
    $rows = db_fetch_all(
        "SELECT r.report_no, r.subject, r.category, r.location, r.severity, r.building,
                d.name AS department_name, r.status, r.submitted_at, r.details,
                COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) AS resolved_at
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         ORDER BY r.submitted_at DESC",
        $f['types'],
        $f['params'],
    );
    output_analytics_xlsx($f, $rows, $advancedSummary);
    exit();
}

if ($export === 'pdf') {
    $kpis = get_kpis($f);
    $rows = db_fetch_all(
        "SELECT r.report_no, r.subject, r.severity, r.building,
                d.name AS department_name, r.status, r.submitted_at,
                COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) AS resolved_at
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         ORDER BY r.submitted_at DESC",
        $f['types'],
        $f['params'],
    );
    output_analytics_pdf($f, $kpis, $rows, $advancedSummary);
    exit();
}

$trendMode = trim((string) ($_GET['trend'] ?? 'daily'));
$trendMode = in_array($trendMode, ['daily', 'weekly', 'monthly'], true) ? $trendMode : 'daily';

$payload = [
    'filters' => [
        'start_date' => $f['start'],
        'end_date' => $f['end'],
        'building' => $f['building'] ?? null,
        'department_id' => (int) $f['department_id'],
        'severity' => $f['severity'],
        'status' => $f['status'],
        'tab' => $tab,
        'role' => $role,
        'department_restricted' => (bool) $f['is_department_restricted'],
    ],
    'kpis' => get_kpis($f),
    'trend' => get_trend($trendMode, $f),
    'severity_distribution' => get_severity_distribution($f),
    'by_department' => get_by_department($f, $role, $user),
    'timeline' => get_timeline_performance($f),
    'overdue' => [
        'rows' => get_overdue_rows($f),
    ],
    'department_stats' => $departmentStats,
    'resolution_breakdown' => $resolutionBreakdown,
    'sla_stats' => $slaStats,
    'building_stats' => $buildingStats,
    'comparison_data' => $comparisonData,
    'recurring_issues' => $recurringIssues,
    'detailed_rows' => $detailedRows,
];

header('Content-Type: application/json');
echo json_encode($payload);
