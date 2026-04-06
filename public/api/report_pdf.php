<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user = getUser();
$role = (string) ($user['role'] ?? '');
$allowedRoles = ['ga_manager', 'ga_staff', 'security', 'department', 'pic'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$report = null;
$evidence = [];
$reportNo = trim((string) ($_GET['id'] ?? ''));
$previewToken = trim((string) ($_GET['preview_token'] ?? ''));

if ($previewToken !== '') {
    $pool = $_SESSION['submit_report_pdf_preview'] ?? [];
    $entry =
        is_array($pool) && isset($pool[$previewToken]) && is_array($pool[$previewToken]) ? $pool[$previewToken] : null;

    if (!$entry) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Preview token not found']);
        exit();
    }

    $createdAt = (int) ($entry['created_at'] ?? 0);
    $createdBy = (string) ($entry['created_by'] ?? '');
    $reportData = $entry['report'] ?? null;
    $currentUserNo = (string) ($user['employee_no'] ?? '');

    if (
        !is_array($reportData) ||
        $createdAt <= 0 ||
        time() - $createdAt > 1200 ||
        ($createdBy !== '' && $createdBy !== $currentUserNo)
    ) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Preview token expired or forbidden']);
        exit();
    }

    $report = $reportData;
    $evidence = is_array($entry['evidence'] ?? null) ? $entry['evidence'] : [];
    $reportNo = (string) ($report['report_no'] ?? 'PREVIEW');
} else {
    if ($reportNo === '') {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Missing id']);
        exit();
    }

    $userDepartmentId = (int) ($user['department_id'] ?? 0);

    $whereExtra = '';
    $params = [$reportNo];

    if ($role === 'security') {
    } elseif ($role === 'department' || $role === 'pic') {
        if ($userDepartmentId <= 0) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Account is missing an assigned department']);
            exit();
        }

        // Only view reports where their department is the one responsible for the fix
        $whereExtra = ' AND r.responsible_department_id = ?';
        $params[] = $userDepartmentId;
    }

    // Detect whether migration 007 (job_level column) has been applied to this database.
    // The column is selected only when it exists; downstream code already uses ?? '' for safety.
    $hasJobLevel = (bool) db_fetch_one("SHOW COLUMNS FROM users LIKE 'job_level'");

    $sql =
        "SELECT
            r.id,
            r.report_no,
            r.subject,
            r.category,
            r.sub_category_id,
            r.location,
            r.severity,
            r.building,
            r.security_type,
            r.status,
            r.submitted_at,
            r.details,
            r.assessment,
            r.recommendations,
            r.actions_taken,
            r.remarks,
            r.security_remarks,
            r.fix_due_date,
            d.name AS department_name,
            u_submit.name           AS submitted_by_name,
            u_submit.signature_path AS submitted_by_signature,
            gapa.decided_at,
            gapa.decision           AS ga_manager_decision,
            gapa.notes              AS ga_manager_notes,
            u_pres.name             AS ga_manager_name,
            u_pres.signature_path   AS ga_manager_signature" .
        ($hasJobLevel ? ",\n        u_pres.job_level        AS ga_manager_job_level" : '') .
        ",
            da.action_type,
            da.timeline_days,
            da.timeline_start,
            da.timeline_due,
            da.remarks              AS dept_remarks,
            da.acted_at             AS dept_acted_at,
            u_dept.name             AS dept_acted_by,
            u_dept.signature_path   AS dept_signature,
            sfc.checked_at          AS final_checked_at,
            sfc.decision            AS final_decision,
            sfc.remarks             AS final_remarks,
            u_sec.name              AS final_checked_by,
                sfc.closed_at,
                sc.name                 AS sub_category_name
        FROM reports r
        JOIN departments d         ON d.id = r.responsible_department_id
        LEFT JOIN subcategories sc     ON sc.id = r.sub_category_id
        LEFT JOIN users u_submit        ON u_submit.employee_no = r.submitted_by
        LEFT JOIN ga_manager_approvals gapa ON gapa.report_id = r.id
        LEFT JOIN users u_pres          ON u_pres.employee_no = gapa.decided_by
        LEFT JOIN department_actions da ON da.report_id = r.id
        LEFT JOIN users u_dept          ON u_dept.employee_no = da.acted_by
        LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
        LEFT JOIN users u_sec           ON u_sec.employee_no = sfc.checked_by
        WHERE r.report_no = ?";

    $sql .= $whereExtra . ' LIMIT 1';

    $report = db_fetch_one($sql, '', $params);
    if (!$report) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Report not found']);
        exit();
    }

    $evidenceSql = 'SELECT id, file_path FROM report_attachments WHERE report_id = ?';
    $evidence = db_fetch_all($evidenceSql, '', [$report['id']]);
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function pdf_escape_text(string $s): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

/**
 * FIX #1: norm_security_type now defaults to 'internal' when the value is
 * missing/null, matching the majority of reports in this system.
 * Change the fallback to whichever type is your "default" agency.
 */
function norm_security_type(?string $raw): string
{
    $v = strtolower(trim((string) $raw));
    if ($v === 'internal') {
        return 'internal';
    }
    if ($v === 'external') {
        return 'external';
    }
    // Explicit fallback — set to 'internal' so Aragon reports are never
    // accidentally rendered with the SISCO external header.
    return 'internal';
}

function wrap_pdf_lines(string $text, int $maxLen): array
{
    $text = trim($text);
    if ($text === '') {
        return [];
    }
    $out = [];
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    foreach (explode("\n", $text) as $p) {
        $p = trim($p);
        if ($p === '') {
            continue;
        }
        $words = preg_split('/\s+/', $p, -1, PREG_SPLIT_NO_EMPTY);
        $line = '';
        foreach ($words as $w) {
            if ($line === '') {
                $line = $w;
                continue;
            }
            if (strlen($line) + 1 + strlen($w) <= $maxLen) {
                $line .= ' ' . $w;
            } else {
                $out[] = $line;
                $line = $w;
            }
        }
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function pdf_text(float $x, float $y, string $font, int $size, string $text): string
{
    return sprintf("BT\n/$font %d Tf\n%.2f %.2f Td\n(%s) Tj\nET\n", $size, $x, $y, pdf_escape_text($text));
}

function pdf_line(float $x1, float $y1, float $x2, float $y2, float $width = 1.0): string
{
    return sprintf("%.2f w\n%.2f %.2f m\n%.2f %.2f l\nS\n", $width, $x1, $y1, $x2, $y2);
}

// Approximate character-width factors for PDF Type1 fonts: Helvetica-Bold (F2)=0.52, Helvetica (F1)=0.46
function estimate_text_width(string $text, float $fontSize, string $font): float
{
    return (float) strlen($text) * $fontSize * ($font === 'F2' ? 0.52 : 0.46);
}
const MIN_SIG_NAME_WIDTH = 60.0; // minimum signature width in pt when name is very short

/**
 * FIX #2: $gdAvailableForPdfImages is now determined INSIDE the function
 * (or passed as a parameter) so it is always in scope.
 */
function gd_available(): bool
{
    return function_exists('imagecreatefrompng') &&
        function_exists('imagecreatefromjpeg') &&
        function_exists('imagecolorat') &&
        function_exists('imagecolorsforindex');
}

function build_pdf_image_objects_from_rgba(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    if (!gd_available()) {
        return null;
    }

    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }

    $mime = $info['mime'] ?? '';
    if ($mime === 'image/png') {
        $src = @imagecreatefrompng($path);
    } elseif ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($path);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($path);
    } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
        $src = @imagecreatefromgif($path);
    } else {
        return null;
    }
    if (!$src) {
        return null;
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $rgb = '';
    $alpha = '';
    for ($py = 0; $py < $h; $py++) {
        for ($px = 0; $px < $w; $px++) {
            $c = imagecolorsforindex($src, imagecolorat($src, $px, $py));
            $rgb .= chr($c['red']) . chr($c['green']) . chr($c['blue']);
            $alpha .= chr((int) ((127 - $c['alpha']) * 2));
        }
    }
    imagedestroy($src);

    $rgbS = gzcompress($rgb, 6);
    $maskS = gzcompress($alpha, 6);

    return [
        'maskObj' =>
            "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " .
            strlen($maskS) .
            " >>\nstream\n" .
            $maskS .
            "\nendstream",
        'imgObj' =>
            "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB  /BitsPerComponent 8 /SMask %SMASK% 0 R /Filter /FlateDecode /Length " .
            strlen($rgbS) .
            " >>\nstream\n" .
            $rgbS .
            "\nendstream",
        'w' => $w,
        'h' => $h,
    ];
}

// ── Main PDF builder ──────────────────────────────────────────────────────────
function output_report_template_pdf(array $report, string $filename, array $evidence = []): void
{
    $pageW = 612;
    $pageH = 1008;
    $marginL = 50;
    $marginR = 50;
    $topY = $pageH - 50;
    $bottomY = 55;

    $isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';
    $showGrid = isset($_GET['grid']) && $_GET['grid'] == '1';

    // FIX #2 (continued): call the helper function — no global variable needed
    $gdAvailable = gd_available();

    // ── Dynamic names ────────────────────────────────────────────────────────
    $gaManager = !empty($report['ga_manager_name']) ? strtoupper($report['ga_manager_name']) : 'NOT YET APPROVED';
    $departmentPic = !empty($report['dept_acted_by'])
        ? strtoupper((string) $report['dept_acted_by'])
        : strtoupper(trim((string) ($report['department_name'] ?? 'DEPARTMENT')) . ' PIC');
    $departmentPicRoleLabel = strtoupper((string) ($report['department_name'] ?? 'DEPARTMENT'));

    // Dynamic role labels: "GA " + job_level when available, otherwise a role-based fallback.
    // Shown when the person has actually approved/reviewed the report.
    $gaManagerRoleLabel = '';
    if (!empty($report['ga_manager_name'])) {
        $jl = trim((string) ($report['ga_manager_job_level'] ?? ''));
        $gaManagerRoleLabel = $jl !== '' ? 'GA ' . strtoupper($jl) : 'GA MANAGER';
    }
    $subjectName = trim((string) ($report['subject'] ?? ''));
    $categoryName = trim((string) ($report['category'] ?? ''));
    $subCategoryName = trim((string) ($report['sub_category_name'] ?? ($report['sub_category'] ?? '')));
    if ($subjectName === '') {
        $subjectName = 'Subject';
    }
    if ($categoryName === '') {
        $categoryName = 'Category';
    }
    if ($subCategoryName === '') {
        $subCategoryName = 'Sub-Category';
    }
    $subjectLine = strtoupper($subjectName . ': ' . $categoryName . ' - ' . $subCategoryName);
    $dateStr = !empty($report['submitted_at']) ? date('d F Y', strtotime($report['submitted_at'])) : date('d F Y');

    // Use report-level security_type as the single source of truth
    $template = norm_security_type($report['security_type'] ?? null);

    // ── Header text lines (depend entirely on $template) ─────────────────────
    if ($template === 'internal') {
        $headerLines = [
            ['font' => 'F2', 'size' => 14, 'text' => 'ARAGON SECURITY AND INVESTIGATION'],
            ['font' => 'F2', 'size' => 13, 'text' => 'AGENCY, CORPORATION'],
            ['font' => 'F1', 'size' => 10, 'text' => 'NIDEC PHILIPPINES CORPORATION DETACHMENT'],
            [
                'font' => 'F1',
                'size' => 9,
                'text' => '136 North Science Avenue Extension, Laguna Technopark, Binan, Laguna',
            ],
        ];
    } else {
        // EXTERNAL (SISCO)
        $headerLines = [
            ['font' => 'F2', 'size' => 14, 'text' => 'SISCO INVESTIGATION & SECURITY CORPORATION'],
            ['font' => 'F1', 'size' => 12, 'text' => 'NIDEC Philippines Corporation - Security Detachment'],
            [
                'font' => 'F1',
                'size' => 10,
                'text' => '119 Technology Avenue Special Economic Zone Laguna Technopark, Binan Laguna',
            ],
        ];
    }

    $pages = [];
    $content = '';
    $y = $topY;

    // ── Page renderer (closure) ───────────────────────────────────────────────
    $isFirstPage = true;
    $start_new_page = function () use (
        &$pages,
        &$content,
        &$y,
        $topY,
        $pageW,
        $template,
        $headerLines,
        $pageH,
        $showGrid,
        &$isFirstPage,
    ): void {
        if ($content !== '') {
            $pages[] = $content;
        }
        $content = '';
        $y = $topY;

        if (!$isFirstPage) {
            return; // No header on subsequent pages
        }
        $isFirstPage = false;

        // Optional debug grid
        if ($showGrid) {
            $content .= "q 0.8 G 0.3 w\n";
            for ($gx = 0; $gx <= $pageW; $gx += 50) {
                $content .= "$gx 0 m $gx $pageH l S\nBT /F1 8 Tf $gx 5 Td ($gx) ET\n";
            }
            for ($gy = 0; $gy <= $pageH; $gy += 50) {
                $content .= "0 $gy m $pageW $gy l S\nBT /F1 8 Tf 5 $gy Td ($gy) ET\n";
            }
            $content .= "Q\n";
        }

        // ── Centered header text ──────────────────────────────────────────────
        $textAreaWidth = 320;
        $tx = $pageW / 2 - $textAreaWidth / 2;

        foreach ($headerLines as $idx => $hl) {
            $lineY = $y - $idx * 16;
            $fontSize = (int) $hl['size'];
            $fontObj = $hl['font'];
            $text = (string) $hl['text'];

            $widthFactor = $fontObj === 'F2' ? 0.52 : 0.46;
            $estimatedLineWidth = strlen($text) * ($fontSize * $widthFactor);
            $lineX = $tx + $textAreaWidth / 2 - $estimatedLineWidth / 2;

            if ($template === 'internal') {
                if ($idx === 0) {
                    // ARAGON Line logic
                    $fullWidth = strlen('ARAGON SECURITY AND INVESTIGATION') * ($fontSize * 0.52);
                    $line1X = $tx + $textAreaWidth / 3 - $fullWidth / 3;
                    $content .= "0.7 0 0 rg\n";
                    $content .= pdf_text($line1X, $lineY, 'F2', $fontSize, 'ARAGON ');
                    $content .= "0 0.3 0.6 rg\n";
                    $content .= pdf_text($line1X + 68, $lineY, 'F2', $fontSize, 'SECURITY AND INVESTIGATION');
                } elseif ($idx === 1) {
                    // AGENCY, CORPORATION Line
                    $content .= "0 0.3 0.6 rg\n";
                    $content .= pdf_text($lineX - 6, $lineY, 'F2', $fontSize, $text);
                } elseif ($idx === 2) {
                    // TARGET: NIDEC PHILIPPINES CORPORATION DETACHMENT
                    // Adjust the '+ 5' below until it looks perfectly centered to you
                    $content .= "0 0 0 rg\n";
                    $content .= pdf_text($lineX - 20, $lineY, $fontObj, $fontSize, $text);
                } else {
                    // Address line (index 3)
                    $content .= "0 0 0 rg\n";
                    $content .= pdf_text($lineX + 1, $lineY, $fontObj, $fontSize, $text);
                }
            } else {
                // EXTERNAL (SISCO) Template
                $content .= "0 0 0 rg\n";

                if ($idx === 0) {
                    // SISCO Main Name
                    // Adjust +0 to move this specific line
                    $content .= pdf_text($lineX - 18, $lineY, $fontObj, $fontSize, $text);
                } elseif ($idx === 1) {
                    // NIDEC Philippines Corporation - Security Detachment
                    // Adjust the +5 (or any number) to center this line visually
                    $content .= pdf_text($lineX + 5, $lineY, $fontObj, $fontSize, $text);
                } else {
                    // Address Line (Index 2)
                    // Usually stays at 0 for mathematical centering
                    $content .= pdf_text($lineX - 10, $lineY, $fontObj, $fontSize, $text);
                }
            }
        }
        $y -= $template === 'internal' ? 100 : 92;
    };

    $start_new_page();

    // Image objects shared between memo header sigs, evidence, and bottom signatories
    $evidenceImageObjects = [];

    // ── Memo fields (internal vs external layout) ─────────────────────────────
    if ($template === 'internal') {
        // FOR (Department PIC) — signature above name when available
        $content .= pdf_text($marginL, $y, 'F2', 11, 'FOR');
        $_presigH = 0.0;
        if (!empty($report['dept_signature'])) {
            $sp =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace('/', DIRECTORY_SEPARATOR, (string) $report['dept_signature']);
            $sd = $gdAvailable ? build_pdf_image_objects_from_rgba($sp) : null;
            if ($sd) {
                $evidenceImageObjects['ImSigDeptHeader'] = $sd;
                $_psc = min(55.0 / (float) $sd['h'], 160.0 / (float) $sd['w']);
                $_psw = $sd['w'] * $_psc;
                $_psh = $sd['h'] * $_psc;
                $content .= sprintf(
                    "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/ImSigDeptHeader Do\nQ\n",
                    $_psw,
                    $_psh,
                    $marginL + 90,
                    $y - $_psh,
                );
                $_presigH = $_psh + 4.0;
            }
        }
        $content .= pdf_text($marginL + 85, $y - $_presigH, 'F1', 11, ': ' . $departmentPic);
        if ($departmentPicRoleLabel !== '') {
            $content .= pdf_text($marginL + 95, $y - $_presigH - 14, 'F1', 10, $departmentPicRoleLabel);
        }
        $y -= 45.0 + $_presigH;

        // THRU (GA Manager) — signature above name
        $content .= pdf_text($marginL, $y, 'F2', 11, 'THRU');
        $_stafgH = 0.0;
        if (!empty($report['ga_manager_signature'])) {
            $sp =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace('/', DIRECTORY_SEPARATOR, (string) $report['ga_manager_signature']);
            $sd = $gdAvailable ? build_pdf_image_objects_from_rgba($sp) : null;
            if ($sd) {
                $evidenceImageObjects['ImSigPresHeader'] = $sd;
                $_ssc = min(55.0 / (float) $sd['h'], 160.0 / (float) $sd['w']);
                $_ssw = $sd['w'] * $_ssc;
                $_ssh = $sd['h'] * $_ssc;
                $content .= sprintf(
                    "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/ImSigPresHeader Do\nQ\n",
                    $_ssw,
                    $_ssh,
                    $marginL + 90,
                    $y - $_ssh,
                );
                $_stafgH = $_ssh + 4.0;
            }
        }
        $content .= pdf_text($marginL + 85, $y - $_stafgH, 'F1', 11, ': ' . $gaManager);
        if ($gaManagerRoleLabel !== '') {
            $content .= pdf_text($marginL + 95, $y - $_stafgH - 14, 'F1', 10, $gaManagerRoleLabel);
        }
        $y -= 45.0 + $_stafgH;

        $content .= pdf_text($marginL, $y, 'F2', 11, 'SUBJECT');
        $content .= pdf_text($marginL + 85, $y, 'F2', 11, ': ');
        foreach (wrap_pdf_lines($subjectLine, 50) as $i => $line) {
            if ($i > 0) {
                $y -= 14;
            }
            $content .= pdf_text($marginL + 95, $y, 'F2', 11, $line);
        }
        $y -= 35;
        $content .= pdf_text($marginL, $y, 'F2', 11, 'DATE');
        $content .= pdf_text($marginL + 85, $y, 'F1', 11, ': ' . strtoupper($dateStr));
        $y -= 25;
        $content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5);
        $y -= 30;
    } else {
        // EXTERNAL (SISCO) memo fields
        $content .= pdf_text($marginL, $y, 'F2', 11, 'DATE');
        $content .= pdf_text($marginL + 70, $y, 'F1', 11, ': ' . $dateStr);
        $y -= 35;
        // The duplicate line below has been removed
        // $content .= pdf_text($marginL, $y, 'F1', 11, 'Date                : ' . $dateStr); $y -= 20;
        // } else {
        //     // EXTERNAL (SISCO) memo fields
        //     $content .= pdf_text($marginL, $y, 'F2', 11, 'DATE');
        //     $content .= pdf_text($marginL + 70, $y, 'F1', 11, ': ' . $dateStr); $y -= 35;$content .= pdf_text($marginL, $y, 'F1', 11, 'Date                : ' . $dateStr); $y -= 20;

        // TO (Department PIC) — signature above name when available
        $content .= pdf_text($marginL, $y, 'F2', 11, 'TO');
        $_presigH = 0.0;
        if (!empty($report['dept_signature'])) {
            $sp =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace('/', DIRECTORY_SEPARATOR, (string) $report['dept_signature']);
            $sd = $gdAvailable ? build_pdf_image_objects_from_rgba($sp) : null;
            if ($sd) {
                $evidenceImageObjects['ImSigDeptHeader'] = $sd;
                $_psc = min(55.0 / (float) $sd['h'], 160.0 / (float) $sd['w']);
                $_psw = $sd['w'] * $_psc;
                $_psh = $sd['h'] * $_psc;
                $content .= sprintf(
                    "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/ImSigDeptHeader Do\nQ\n",
                    $_psw,
                    $_psh,
                    $marginL + 80,
                    $y - $_psh,
                );
                $_presigH = $_psh + 4.0;
            }
        }
        $content .= pdf_text($marginL + 70, $y - $_presigH, 'F1', 11, ': ' . $departmentPic);
        if ($departmentPicRoleLabel !== '') {
            $content .= pdf_text($marginL + 80, $y - $_presigH - 14, 'F1', 10, $departmentPicRoleLabel);
        }
        $y -= 45.0 + $_presigH;

        // THRU (GA Manager) — signature above name
        $content .= pdf_text($marginL, $y, 'F2', 11, 'THRU');
        $_stafgH = 0.0;
        if (!empty($report['ga_manager_signature'])) {
            $sp =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                str_replace('/', DIRECTORY_SEPARATOR, (string) $report['ga_manager_signature']);
            $sd = $gdAvailable ? build_pdf_image_objects_from_rgba($sp) : null;
            if ($sd) {
                $evidenceImageObjects['ImSigPresHeader'] = $sd;
                $_ssc = min(55.0 / (float) $sd['h'], 160.0 / (float) $sd['w']);
                $_ssw = $sd['w'] * $_ssc;
                $_ssh = $sd['h'] * $_ssc;
                $content .= sprintf(
                    "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/ImSigPresHeader Do\nQ\n",
                    $_ssw,
                    $_ssh,
                    $marginL + 80,
                    $y - $_ssh,
                );
                $_stafgH = $_ssh + 4.0;
            }
        }
        $content .= pdf_text($marginL + 70, $y - $_stafgH, 'F1', 11, ': ' . $gaManager);
        if ($gaManagerRoleLabel !== '') {
            $content .= pdf_text($marginL + 80, $y - $_stafgH - 14, 'F1', 10, $gaManagerRoleLabel);
        }
        $y -= 45.0 + $_stafgH;

        $content .= pdf_text($marginL, $y, 'F2', 11, 'SUBJECT');
        $subjectLines = wrap_pdf_lines(': ' . $subjectLine, 50);
        foreach ($subjectLines as $i => $sLine) {
            $indent = $i === 0 ? 70 : 77;
            $content .= pdf_text($marginL + $indent, $y, 'F2', 11, $sLine);
            if (count($subjectLines) > 1 && $i < count($subjectLines) - 1) {
                $y -= 14;
            }
        }
        $y -= 30;
        $content .= pdf_line($marginL, $y, $pageW - $marginR, $y, 0.5);
        $y -= 35;
    }

    // ── Body sections ─────────────────────────────────────────────────────────
    $bodySections = [
        'Details' => $report['details'] ?? '',
        'Assessment' => $report['assessment'] ?? '',
        'Recommendations' => $report['recommendations'] ?? '',
        'Action Taken' => $report['actions_taken'] ?? '',
        'Remarks' => $report['remarks'] ?? '',
    ];
    foreach ($bodySections as $label => $val) {
        $val = trim((string) $val);
        if ($val === '' || strtolower($val) === 'n/a') {
            continue;
        }
        $content .= pdf_text($marginL, $y, 'F2', 11, $label . ':');
        $y -= 16;
        foreach (wrap_pdf_lines($val, 95) as $line) {
            if ($y < $bottomY + 20) {
                $start_new_page();
            }
            $content .= pdf_text($marginL + 15, $y, 'F1', 10, $line);
            $y -= 14;
        }
        $y -= 15;
    }

    // ── Evidence / Attachments ────────────────────────────────────────────────
    if (!empty($evidence)) {
        if ($y < 200) {
            $start_new_page();
        }
        $content .= pdf_text($marginL, $y, 'F2', 11, 'Evidence / Attachments:');
        $y -= 30;

        if (!$gdAvailable) {
            // FIX #2: uses the local $gdAvailable — not a missing global
            $content .= pdf_text($marginL + 15, $y, 'F1', 10, 'Images omitted (PHP GD extension is not enabled).');
            $y -= 20;
        } else {
            foreach ($evidence as $img) {
                $imgLocalPath =
                    dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $img['file_path']);
                if (!is_file($imgLocalPath)) {
                    continue;
                }
                $evidImg = build_pdf_image_objects_from_rgba($imgLocalPath);
                if (!$evidImg) {
                    continue;
                }
                $dispW = 400.0;
                $dispH = ($evidImg['h'] / $evidImg['w']) * $dispW;
                if ($y - $dispH < 100) {
                    $start_new_page();
                }
                $imgRef = 'ImEvid' . $img['id'];
                $content .= sprintf(
                    "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/$imgRef Do\nQ\n",
                    $dispW,
                    $dispH,
                    $marginL,
                    $y - $dispH,
                );
                $y -= $dispH + 30;
                $evidenceImageObjects[$imgRef] = $evidImg;
            }
        }
    }

    // ── Signatory blocks ──────────────────────────────────────────────────────
    $y -= 30;
    // Reserve enough space for: label + gap(14) + signature image(max 40) + name + two description lines
    if ($y < 250) {
        $start_new_page();
    }

    // Security and Department only — GA staff/president have their sigs in the header
    $signatories = [];

    // 1. Security — always shown
    $signatories[] = [
        'label' => 'Prepared by:',
        'name' => strtoupper((string) ($report['submitted_by_name'] ?? 'OFFICER NAME')),
        'line1' =>
            $template === 'internal'
                ? strtoupper((string) ($report['building'] ?? 'NCFL')) . ' / Security Officer'
                : 'Detachment Commander',
        'line2' =>
            $template === 'internal'
                ? 'Internal Security'
                : 'SISCO-' . strtoupper((string) ($report['building'] ?? 'NCFL')) . ' External Scty.',
        'sig' => $report['submitted_by_signature'] ?? null,
        'key' => 'security',
    ];

    // Load signature images
    $sigImgMaxH = 40.0;
    foreach ($signatories as &$sig) {
        if (empty($sig['sig'])) {
            continue;
        }
        $sigImgPath =
            dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $sig['sig']);
        if (!is_file($sigImgPath)) {
            continue;
        }
        $sigImgData = build_pdf_image_objects_from_rgba($sigImgPath);
        if (!$sigImgData) {
            continue;
        }
        $sigRef = 'ImSig' . $sig['key'];
        $evidenceImageObjects[$sigRef] = $sigImgData;
        $sig['img_ref'] = $sigRef;
        $sig['img_w'] = $sigImgData['w'];
        $sig['img_h'] = $sigImgData['h'];
    }
    unset($sig);

    // Draw columns — signature image directly above printed name, no separator line
    $numSigs = count($signatories);
    $colWidth = (float) ($pageW - $marginL - $marginR) / max($numSigs, 1);

    foreach ($signatories as $ci => $sig) {
        $cx = (float) $marginL + $ci * $colWidth;
        $content .= pdf_text($cx, $y, 'F1', 10, $sig['label']);
        $rowY = $y - 14.0;
        if (!empty($sig['img_ref'])) {
            $iw = (float) $sig['img_w'];
            $ih = (float) $sig['img_h'];
            $scale = min($sigImgMaxH / $ih, ($colWidth - 6.0) / $iw);
            $dw = $iw * $scale;
            $dh = $ih * $scale;
            $content .= sprintf(
                "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/%s Do\nQ\n",
                $dw,
                $dh,
                $cx,
                $rowY - $dh,
                $sig['img_ref'],
            );
            $nameY = $rowY - $dh - 4.0;
        } else {
            $nameY = $rowY;
        }
        $content .= pdf_text($cx, $nameY, 'F2', 10, $sig['name']);
        if (!empty($sig['line1'])) {
            $content .= pdf_text($cx, $nameY - 14.0, 'F1', 10, $sig['line1']);
        }
        if (!empty($sig['line2'])) {
            $content .= pdf_text($cx, $nameY - 28.0, 'F1', 10, $sig['line2']);
        }
    }

    // ── Assemble PDF objects ──────────────────────────────────────────────────
    if ($content !== '') {
        $pages[] = $content;
    }

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids %KIDS% /Count %COUNT% >>\nendobj\n";
    $objects[] =
        "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica      /Encoding /WinAnsiEncoding >>\nendobj\n";
    $objects[] =
        "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

    $evidMap = [];
    $nextObjIdx = count($objects) + 1;
    foreach ($evidenceImageObjects as $ref => $data) {
        if (!empty($data['maskObj'])) {
            $maskIdx = $nextObjIdx++;
            $imgIdx = $nextObjIdx++;
            $objects[] = "$maskIdx 0 obj\n" . $data['maskObj'] . "\nendobj\n";
            $objects[] = "$imgIdx 0 obj\n" . str_replace('%SMASK%', (string) $maskIdx, $data['imgObj']) . "\nendobj\n";
        } else {
            $imgIdx = $nextObjIdx++;
            $objects[] = "$imgIdx 0 obj\n" . $data['imgObj'] . "\nendobj\n";
        }
        $evidMap[$ref] = $imgIdx;
    }

    $pageNums = [];
    $nextObj = $nextObjIdx;
    foreach ($pages as $pageContent) {
        $contentObjNum = $nextObj++;
        $pageObjNum = $nextObj++;
        $xRefs = '';
        foreach ($evidMap as $ref => $idx) {
            $xRefs .= "/$ref $idx 0 R ";
        }
        $objects[] =
            $contentObjNum .
            " 0 obj\n<< /Length " .
            strlen($pageContent) .
            " >>\nstream\n" .
            $pageContent .
            "endstream\nendobj\n";
        $res = "<< /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xRefs >> >>";
        $objects[] =
            $pageObjNum .
            " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Resources $res /Contents $contentObjNum 0 R >>\nendobj\n";
        $pageNums[] = $pageObjNum;
    }

    $kids = '[ ' . implode(' ', array_map(fn($n) => $n . ' 0 R', $pageNums)) . ' ]';
    $objects[1] = str_replace(['%KIDS%', '%COUNT%'], [$kids, (string) count($pageNums)], $objects[1]);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }
    $xrefPos = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/pdf');
    $disposition = $isPreview || $showGrid ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit();
}

// ── Entry point ───────────────────────────────────────────────────────────────
$reportNoOut = (string) ($report['report_no'] ?? $reportNo);
$filename = 'report_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportNoOut) . '_' . date('Ymd_His') . '.pdf';
output_report_template_pdf($report, $filename, $evidence);
