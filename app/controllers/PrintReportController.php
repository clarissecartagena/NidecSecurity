<?php

namespace App\Controllers;

class PrintReportController extends BaseController
{
    public function show(): void
    {
        require_once __DIR__ . '/../../includes/config.php';

        // Standalone print page (do NOT include main layout header/sidebar/topnav)
        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit();
        }

        $user = getUser();

        $reportIdRaw = trim($_GET['report_id'] ?? '');
        if ($reportIdRaw === '' || !preg_match('/^\d+$/', $reportIdRaw)) {
            http_response_code(400);
            die('Invalid report_id');
        }

        $reportId = (int) $reportIdRaw;
        if ($reportId <= 0) {
            http_response_code(400);
            die('Invalid report_id');
        }

        $report = db_fetch_one(
            "SELECT
                r.id,
                r.report_no,
                r.subject,
                r.category,
                r.location,
                r.severity,
                r.building,
                r.security_type,
                r.responsible_department_id,
                r.status,
                r.details,
                r.actions_taken,
                r.remarks,
                r.evidence_image_path,
                r.security_remarks,
                r.submitted_at,
                d.name AS department_name,
                u_submit.name AS submitted_by_name,
                r.security_type AS submitted_by_security_type,
                gasr.reviewed_at,
                gasr.notes AS ga_staff_notes,
                u_staff.name AS ga_staff_reviewer,
                gapa.decided_at,
                gapa.decision AS ga_manager_decision,
                gapa.notes AS ga_manager_notes,
                u_pres.name AS ga_manager_name
             FROM reports r
             JOIN departments d ON d.id = r.responsible_department_id
             LEFT JOIN users u_submit ON u_submit.employee_no = r.submitted_by
                 LEFT JOIN ga_staff_reviews gasr
                     ON gasr.report_id = r.id
                    AND gasr.reviewed_at = (SELECT MAX(x.reviewed_at) FROM ga_staff_reviews x WHERE x.report_id = r.id)
                 LEFT JOIN users u_staff ON u_staff.employee_no = gasr.reviewed_by
                      LEFT JOIN ga_manager_approvals gapa
                     ON gapa.report_id = r.id
                          AND gapa.decided_at = (SELECT MAX(y.decided_at) FROM ga_manager_approvals y WHERE y.report_id = r.id)
                 LEFT JOIN users u_pres ON u_pres.employee_no = gapa.decided_by
             WHERE r.id = ?
             LIMIT 1",
            'i',
            [$reportId],
        );

        if (!$report) {
            http_response_code(404);
            die('Report not found');
        }

        $canViewReport = static function (array $userRow, array $reportRow): bool {
            $role = (string) ($userRow['role'] ?? '');

            if (in_array($role, ['ga_staff', 'ga_manager'], true)) {
                return true;
            }

            if ($role === 'security') {
                $userBuilding = normalize_building($userRow['entity'] ?? null);
                $reportBuilding = normalize_building($reportRow['building'] ?? null);
                if (!$userBuilding || !$reportBuilding) {
                    return false;
                }
                return $userBuilding === $reportBuilding;
            }

            if ($role === 'department') {
                $userDept = $userRow['department_id'] ?? null;
                if ($userDept === null) {
                    return false;
                }
                return (int) $userDept === (int) ($reportRow['responsible_department_id'] ?? 0);
            }

            return false;
        };

        if (!$canViewReport($user, $report)) {
            http_response_code(403);
            die('Access denied.');
        }

        $fmtDateFull = static function (?string $dateString): string {
            if (!$dateString) {
                return '—';
            }
            $ts = strtotime($dateString);
            if (!$ts) {
                return $dateString;
            }
            return date('F d, Y', $ts);
        };

        $fmtDateShortMemo = static function (?string $dateString): string {
            if (!$dateString) {
                return '—';
            }
            $ts = strtotime($dateString);
            if (!$ts) {
                return $dateString;
            }
            return date('M. d, Y', $ts);
        };

        $fmtDateTime = static function (?string $dateString): string {
            if (!$dateString) {
                return '—';
            }
            $ts = strtotime($dateString);
            if (!$ts) {
                return $dateString;
            }
            return date('M d, Y g:i A', $ts);
        };

        $safePublicRelPath = static function (?string $rel): ?string {
            if (!$rel) {
                return null;
            }
            $rel = str_replace('\\', '/', trim($rel));
            $rel = ltrim($rel, '/');
            if ($rel === '') {
                return null;
            }
            if (str_contains($rel, '..')) {
                return null;
            }
            return $rel;
        };

        $normSecurityType = static function (?string $raw): string {
            $v = strtolower(trim((string) $raw));
            return in_array($v, ['internal', 'external'], true) ? $v : 'external';
        };

        $reportSecurityType = $normSecurityType($report['submitted_by_security_type'] ?? null);
        $template = $reportSecurityType; // 'internal' | 'external'

        // Template branding + logo
        $logoFile = $template === 'internal' ? 'assets/images/internal-logo.png' : 'assets/images/external-logo.png';
        $logoPath = __DIR__ . '/../../public/' . str_replace('/', DIRECTORY_SEPARATOR, $logoFile);
        $logoUrl = is_file($logoPath) ? app_url($logoFile) : null;

        $headerLines = [];
        if ($template === 'internal') {
            // Internal header must match the provided internal PDF templates.
            $headerLines = [
                [
                    'class' => 'memo-h1',
                    'parts' => [
                        ['class' => 'memo-h1-aragon', 'text' => 'ARAGON'],
                        ['class' => 'memo-h1-rest', 'text' => ' SECURITY AND INVESTIGATION'],
                    ],
                ],
                ['class' => 'memo-h2', 'text' => 'AGENCY, CORPORATION'],
                ['class' => 'memo-h3', 'text' => 'NIDEC PHILIPPINES CORPORATION DETACHMENT'],
                [
                    'class' => 'memo-h4',
                    'text' => '136 North Science Avenue Extension, Laguna Technopark, Binan, Laguna',
                ],
            ];
        } else {
            $headerLines = [
                ['class' => 'memo-h1', 'text' => 'SISCO INVESTIGATION & SECURITY CORPORATION'],
                ['class' => 'memo-h2', 'text' => 'NIDEC Philippines Corporation - Security Detachment'],
                [
                    'class' => 'memo-h3',
                    'text' => '119 Technology Avenue Special Economic Zone Laguna Technopark, Biñan Laguna',
                ],
            ];
        }

        $reportNo = (string) ($report['report_no'] ?? '');
        $subject = (string) ($report['subject'] ?? '');

        $description = (string) ($report['details'] ?? '');
        $actionTaken = (string) ($report['actions_taken'] ?? '');
        $remarks = (string) ($report['remarks'] ?? '');
        $securityRemarks = (string) ($report['security_remarks'] ?? '');
        $gaStaffNotes = (string) ($report['ga_staff_notes'] ?? '');

        $evidenceRel = $safePublicRelPath($report['evidence_image_path'] ?? null);
        $evidenceUrl = $evidenceRel ? app_url($evidenceRel) : null;

        $rawAttachments = db_fetch_all(
            'SELECT file_name, file_path, mime_type, uploaded_at FROM report_attachments WHERE report_id = ? ORDER BY uploaded_at ASC',
            'i',
            [$reportId],
        );

        $attachments = [];
        $imageAttachmentUrl = null;
        foreach ($rawAttachments as $a) {
            $fileName = (string) ($a['file_name'] ?? '');
            $filePathRel = $safePublicRelPath($a['file_path'] ?? null);
            $mimeType = (string) ($a['mime_type'] ?? '');
            $uploadedAt = (string) ($a['uploaded_at'] ?? '');

            $attachments[] = [
                'file_name' => $fileName,
                'file_path_rel' => $filePathRel,
                'mime_type' => $mimeType,
                'uploaded_at_fmt' => $uploadedAt !== '' ? $fmtDateTime($uploadedAt) : '',
            ];

            if ($imageAttachmentUrl === null && $filePathRel && str_starts_with($mimeType, 'image/')) {
                $imageAttachmentUrl = app_url($filePathRel);
            }
        }

        $attachmentImageUrl = $evidenceUrl ?: $imageAttachmentUrl;

        $gaStaffReviewer = (string) ($report['ga_staff_reviewer'] ?? '');
        $gaManagerName = (string) ($report['ga_manager_name'] ?? '');

        // Memo recipients (dynamic)
        $memoToTitle = 'GA Manager';

        $pres = db_fetch_one(
            "SELECT name FROM users WHERE role = 'ga_manager' AND (account_status IS NULL OR account_status = 'active') ORDER BY created_at DESC LIMIT 1",
        );
        $memoToName = (string) ($pres['name'] ?? '');
        if ($memoToName === '') {
            $memoToName = $gaManagerName;
        }
        if ($memoToName === '') {
            $memoToName = '—';
        }

        $thruRow = db_fetch_one(
            "SELECT u.name AS name
             FROM report_status_history h
             JOIN users u ON u.employee_no = h.changed_by
             WHERE h.report_id = ? AND h.status = 'submitted_to_ga_manager' AND u.role = 'ga_staff'
             ORDER BY h.changed_at DESC
             LIMIT 1",
            'i',
            [$reportId],
        );
        $thruName = (string) ($thruRow['name'] ?? '');
        if ($thruName === '') {
            $thruName = $gaStaffReviewer;
        }

        $memoThru = [];
        if ($thruName !== '') {
            $memoThru[] = ['name' => $thruName, 'title' => 'GA Staff'];
        }

        $memoPrefix = $template === 'internal' ? 'Observation Report - ' : 'Violation Report - ';
        $memoSubject = $memoPrefix . ($subject !== '' ? $subject : ($reportNo !== '' ? $reportNo : '#' . $reportId));

        $memoDate = $fmtDateFull($report['submitted_at'] ?? null);
        $memoFooterDate = $fmtDateShortMemo($report['submitted_at'] ?? null);

        $reportBuilding = normalize_building($report['building'] ?? null);
        if (!$reportBuilding) {
            $reportBuilding = normalize_building($user['entity'] ?? null);
        }

        $reportingOfficer = (string) ($report['submitted_by_name'] ?? '');
        $preparedByName = $reportingOfficer;
        if ($preparedByName === '') {
            $preparedByName = (string) ($user['name'] ?? '');
        }
        if ($preparedByName === '') {
            $preparedByName = '—';
        }

        $preparedByTitle1 = $template === 'internal' ? 'Security Department' : 'Security Detachment';
        $preparedByTitle2 =
            $template === 'internal'
                ? 'NIDEC Internal Security' . ($reportBuilding ? ' - ' . $reportBuilding : '')
                : 'SISCO-' . ($reportBuilding ?: 'NCFL') . ' External Scty.';

        $dec = trim((string) ($report['ga_manager_decision'] ?? ''));
        $decNotes = trim((string) ($report['ga_manager_notes'] ?? ''));
        $decBy = trim((string) ($report['ga_manager_name'] ?? ''));
        $decAt = trim((string) ($report['decided_at'] ?? ''));
        $decisionLine = '';
        if ($dec !== '') {
            $decisionLine = $dec;
        }
        if ($decBy !== '') {
            $decisionLine .= ($decisionLine !== '' ? ' — ' : '') . $decBy;
        }
        if ($decAt !== '') {
            $decisionLine .= $decisionLine !== '' ? ' (' . $fmtDateTime($decAt) . ')' : $fmtDateTime($decAt);
        }

        require __DIR__ . '/../../views/reports/print_report.php';
    }
}
