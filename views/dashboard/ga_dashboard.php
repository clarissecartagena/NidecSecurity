<?php
// Set timezone to ensure the correct time is detected
date_default_timezone_set('Asia/Manila');

// 1. Current Time & Greeting Logic
$hour = date('H');
$currentDate = date('l, F j, Y'); // For the header greeting

if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

// 2. Daily Summary Logic (Yesterday's Report)
$yesterdayDate = date('Y-m-d', strtotime('-1 day'));
$displayReportDate = date('F j, Y', strtotime('-1 day')); // e.g., March 23, 2026

// 3. Counting Yesterday's Reports
$yesterdayCountSql = 'SELECT COUNT(*) as total FROM reports WHERE DATE(submitted_at) = ?';
$yesterdayData = db_fetch_one($yesterdayCountSql, '', [$yesterdayDate]);
$count = $yesterdayData['total'] ?? 0;

// 4. "Wait For" Logic (Tracking Today's Progress)
// This is used for the text: "Wait for [Next Report Date] for the [Current Summary] summary"
$nextReportDate = date('F j, Y', strtotime('+1 day')); // Tomorrow's date
$currentSummaryDate = date('F j, Y');

// Today's date
?>

<style>
    /* 1. TABLE STYLES */
    .table-container table.ga-staff-table {
        table-layout: fixed !important;
        width: 100% !important;
        border-collapse: collapse;
    }

    .ga-staff-table th { 
        text-align: center !important; 
        font-size: 0.875rem !important;
        padding: 12px 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }

    .ga-staff-table td {
        text-align: center !important;
        vertical-align: middle;
        font-size: 0.875rem !important;
        padding: 12px 8px;
    }

    .ga-staff-table td.subject-cell {
        text-align: left !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ga-staff-table th:nth-child(1) { width: 110px; }
    .ga-staff-table th:nth-child(2) { width: auto;  }
    .ga-staff-table th:nth-child(3) { width: 150px; }
    .ga-staff-table th:nth-child(4) { width: 100px; }
    .ga-staff-table th:nth-child(5) { width: 140px; }
    .ga-staff-table th:nth-child(6) { width: 120px; }

    /* 1. LAYOUT & SYNCING */
    .align-items-stretch {
        display: flex;
        align-items: stretch;
    }

    .h-100 {
        height: 100% !important;
    }

    /* 2. KPI / METRIC CARDS (2x2 Grid Side) */
    .metric-info-text {
        margin-top: 25px; /* Adjust this to move the text group higher or lower */
        z-index: 2;
    }

    .metric-card {
        display: flex !important;
        min-height: 105px !important; /* Change 'height' to 'min-height' */
        position: relative;
        overflow: hidden;
        border: none;
        border-radius: 12px;
        transition: transform 0.2s;
    }

    .metric-card-stacked {
        height: 105px !important;
    }

    .metric-card:hover {
        transform: translateY(-2px);
    }

    /* The Big Number at top-right */
    .metric-card-value-large {
        font-size: 2.8rem !important;
        font-weight: 800;
        line-height: 1;
        margin: 0 !important;
        padding-right: 15px; /* Distance from right wall */
        z-index: 2;
        transition: opacity 0.2s ease; /* Smooth transition if numbers update */
    }

    /* This targets the number specifically when it is zero */
    .metric-card-value-large.value-zero {
        color: #64748b !important; /* A neutral slate grey */
        opacity: 0.3;              /* Makes it look "disabled" or "empty" */
        font-weight: 600;          /* Optional: make it slightly thinner than the active 800 */
    }

    /* The GIANT Overlay Icon at top-left */
    .metric-overlay-icon-left {
        position: absolute !important;
        top: -12px !important;
        left: -15px !important;
        font-size: 5.5rem !important;
        opacity: 0.08;
        z-index: 1;
    }

    .metric-card-footer {
        position: relative;
        z-index: 2;
        
        /* Increase this to lift the text higher */
        margin-bottom: 10px !important; 
        
        padding-left: 5px; 
        /* mt-auto stays here to keep it flexible */
        margin-top: auto; 
    }

    .metric-card-footer .text-base {
        font-size: 1rem !important;
        font-weight: 700;
        line-height: 1.1;
        color: #1a1a1a;
    }

    .metric-card-footer .text-sm {
        margin-top: -3px !important; 
        font-size: 0.75rem !important;
        opacity: 0.7;
        line-height: 1.1;
    }

    /* 3. REPORT SUMMARY CARD (40% Side) */
    /* This class overrides the fixed 105px height from your global metric-card style */
    .summary-report-container {
        height: 226px !important; 
        min-height: 226px !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .summary-report-container, 
    .guide-card-container {
        height: 100% !important; /* Allow it to fill the col-lg-3 height */
        min-height: 226px !important; 
    }

    #reportSummaryCard h6, 
    #reportSummaryCard p, 
    #reportSummaryCard span,
    #reportSummaryCard .text-muted,
    #reportSummaryCard i.bi-info-circle {
        color: #1a1a1a !important; /* Overrides global white text from .metric-card */
    }

    /* Button styles to match the glass/gradient look */
    .btn-summary-outline {
        background: rgba(0, 0, 0, 0.05) !important;
        border: 1.2px solid #1a1a1a !important;
        color: #1a1a1a !important;
        font-weight: 600;
    }

    .btn-summary-solid {
        background: #1a1a1a !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600;
    }

    .border-white-10 {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* Ensure icons don't drift */
    .summary-icon-wrapper {
        min-width: 40px;
        text-align: center;
    }

    .report-summary-card {
        /* REMOVED: background: #fff; */
        background: transparent !important; 
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        height: 226px !important; /* Alignment fix */
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
        border: none;
    }

    /* If the global style still doesn't show, it's likely because it targets 'button' */
    /* This ensures the div is allowed to show the global background */
    div#reportSummaryCard {
        display: flex !important;
    }
    /* Ensure the global classes are forced to show their background */
    #reportSummaryCard.metric-accent-destructive,
    #reportSummaryCard.metric-accent-success {
        /* This ensures your global gradient is visible */
        display: flex !important; 
    }
    #reportSummaryCard {
        background: inherit; /* Uses your metric-accent-destructive/success background */
    }

    .summary-heading {
        color: #000000 !important;
        font-size: 1rem !important; /* Scaled for height */
        margin-bottom: 2px !important;
    }

    .summary-subtext, .summary-footer-text {
        color: #4b5563 !important; /* High contrast Slate Grey */
        font-size: 0.8rem !important;
    }

    .summary-subtext span {
        color: #000000 !important; 
        background: rgba(255, 255, 255, 0.4);
        padding: 0 4px;
        border-radius: 4px;
    }

    .summary-main-icon {
        font-size: 2rem !important;
        color: #000000 !important;
        opacity: 0.9;
    }

    .summary-mode-toggle {
        display: inline-flex;
        gap: 4px;
        padding: 4px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.42);
        border: 1px solid rgba(26, 26, 26, 0.12);
    }

    .summary-mode-btn {
        border: 0;
        border-radius: 999px;
        padding: 4px 11px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #374151;
        background: transparent;
        transition: all 0.18s ease;
    }

    .summary-mode-btn.active {
        color: #ffffff;
        background: #111827;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.22);
    }

    .summary-icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.55);
        border: 1px solid rgba(26, 26, 26, 0.14);
    }

    #reportSummaryCard.metric-accent-destructive .summary-icon-badge {
        color: #b42318;
    }

    #reportSummaryCard.metric-accent-success .summary-icon-badge {
        color: #0f5132;
    }

    .summary-card-title {
        font-size: 0.95rem;
        margin-bottom: 2px !important;
    }

    .summary-date-line {
        font-size: 0.78rem;
        color: #374151 !important;
        margin-bottom: 6px !important;
    }

    .summary-help {
        font-size: 0.74rem;
        line-height: 1.35;
        color: #4b5563 !important;
        margin-bottom: 0 !important;
    }

    .summary-actions .btn {
        font-size: 0.78rem;
        font-weight: 700;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
        line-height: 1.1;
    }

    #summaryFooterNote i {
        margin-right: 5px;
    }

    /* 4. BUTTON STYLES & HOVERS */
    .glass-btn-outline {
        background: rgba(255, 255, 255, 0.2) !important;
        border: 1.5px solid #000000 !important;
        color: #000000 !important;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .glass-btn-outline:hover {
        background: #000000 !important;
        color: #ffffff !important;
    }

    .glass-btn-solid {
        background: #ffffff !important;
        color: #000000 !important;
        border: 1.5px solid #ffffff !important;
        font-weight: 700;
        font-size: 0.85rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }

    .glass-btn-solid:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-1px);
    }

    /* 5. FILTER TOGGLES */
    .custom-filter .btn-check + label {
        color: #4b5563 !important;
        border-color: rgba(0, 0, 0, 0.2) !important;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .custom-filter .btn-check:checked + label {
        background-color: #000000 !important;
        color: #ffffff !important;
        border-color: #000000 !important;
    }

    /* 6. TABLE STYLES */
    .table-container table.ga-staff-table {
        table-layout: fixed !important;
        width: 100% !important;
        border-collapse: collapse;
    }

    .ga-staff-table th { 
        text-align: center !important; 
        font-size: 0.75rem !important;
        padding: 10px 8px;
        text-transform: uppercase;
        color: #64748b;
    }

    .ga-staff-table td {
        text-align: center !important;
        vertical-align: middle;
        font-size: 0.85rem !important;
        padding: 8px;
    }

    .border-white-20 {
        border-color: rgba(0, 0, 0, 0.1) !important;
    }
</style>


<main class="main-content">

    <!-- Title and Greetings Section -->
    <div class="animate-fade-in">
        <div class="mb-2 d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <h1 class="h4 fw-bold text-foreground mb-1">
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
            </h1>
            <p class="fw-semibold text-foreground mb-0">
                <?php echo $greeting; ?>, <?php echo htmlspecialchars($user['displayName'] ?? $user['username']); ?>!
            </p>
            <p class="text-xs text-muted-foreground">
                Today is <?php echo $currentDate; ?>
            </p>
        </div>
    </div>
        
    <!-- Metric Cards -->
    <?php if (($userRole ?? '') === 'ga_manager'): ?>
    <div class="row g-3 mb-4 align-items-stretch">
        
        <div class="col-12 col-md-6 col-lg-3">
            <div class="d-flex flex-column gap-3 h-100">
                <button type="button" class="metric-card metric-accent-warning w-100 p-3 flex-fill" data-metric="pending">
                    <div class="d-flex align-items-center justify-content-between w-100 h-100">
                        <div class="metric-info-text text-start">
                            <p class="text-base fw-bold text-foreground mb-0 ps-3">Pending Approval</p>
                            <p class="text-xs text-muted-foreground mb-0 ps-3">Awaiting final decision.</p>
                        </div>
                        <?php
                        $pendingValue = (int) ($presidentStats['pending_ga'] ?? 0);
                        // If value is 0, add 'value-zero', otherwise leave empty
                        $zeroClass = $pendingValue === 0 ? 'value-zero' : '';
                        ?>
                        <div class="metric-card-value-large <?php echo $zeroClass; ?>"><?php echo $pendingValue; ?></div>
                    </div>
                    <div class="metric-overlay-icon-left"><i class="bi bi-clock"></i></div>
                </button>

                <button type="button" class="metric-card metric-accent-success w-100 p-3 flex-fill" data-metric="in_progress">
                    <div class="d-flex align-items-center justify-content-between w-100 h-100">
                        <div class="metric-info-text text-start">
                            <p class="text-base fw-bold text-foreground mb-0 ps-3">In Progress</p>
                            <p class="text-xs text-muted-foreground mb-0 ps-3">Active fixes and checks.</p>
                        </div>
                        <?php
                        $pendingValue = (int) ($presidentStats['in_progress'] ?? 0);
                        // If value is 0, add 'value-zero', otherwise leave empty
                        $zeroClass = $pendingValue === 0 ? 'value-zero' : '';
                        ?>
                        <div class="metric-card-value-large <?php echo $zeroClass; ?>"><?php echo $pendingValue; ?></div>
                    </div>
                    <div class="metric-overlay-icon-left"><i class="bi bi-hourglass-split"></i></div>
                </button>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="d-flex flex-column gap-3 h-100">
                <button type="button" class="metric-card metric-accent-destructive w-100 p-3 flex-fill" data-metric="critical">
                    <div class="d-flex align-items-center justify-content-between w-100 h-100">
                        <div class="metric-info-text text-start">
                            <p class="text-base fw-bold text-foreground mb-0 ps-3">Critical Severity</p>
                            <p class="text-xs text-muted-foreground mb-0 ps-3">Highest priority incidents.</p>
                        </div>
                        <?php
                        $pendingValue = (int) ($presidentStats['critical'] ?? 0);
                        // If value is 0, add 'value-zero', otherwise leave empty
                        $zeroClass = $pendingValue === 0 ? 'value-zero' : '';
                        ?>
                        <div class="metric-card-value-large <?php echo $zeroClass; ?>"><?php echo $pendingValue; ?></div>
                    </div>
                    <div class="metric-overlay-icon-left"><i class="bi bi-exclamation-triangle"></i></div>
                </button>

                <button type="button" class="metric-card metric-accent-info w-100 p-3 flex-fill" data-metric="overdue">
                    <div class="d-flex align-items-center justify-content-between w-100 h-100">
                        <div class="metric-info-text text-start">
                            <p class="text-base fw-bold text-foreground mb-0 ps-3">Overdue Tasks</p>
                            <p class="text-xs text-muted-foreground mb-0 ps-3">Past due timelines.</p>
                        </div>
                        <?php
                        $pendingValue = (int) ($presidentStats['overdue'] ?? 0);
                        // If value is 0, add 'value-zero', otherwise leave empty
                        $zeroClass = $pendingValue === 0 ? 'value-zero' : '';
                        ?>
                        <div class="metric-card-value-large <?php echo $zeroClass; ?>"><?php echo $pendingValue; ?></div>
                    </div>
                    <div class="metric-overlay-icon-left"><i class="bi bi-exclamation-circle"></i></div>
                </button>
            </div>
        </div>

        <?php
        $pendingApprovalCount = (int) ($presidentStats['pending_ga'] ?? 0);
        $criticalCount = (int) ($presidentStats['critical'] ?? 0);
        $actionReports = array_slice($presidentPending ?? [], 0, 3);

        $actionCtaHref = app_url('ga-manager-approval.php');
        $actionCtaLabel = 'Open Approval Queue →';

        if ($pendingApprovalCount > 0) {
            $actionHeadline =
                'You have ' .
                $pendingApprovalCount .
                ' report' .
                ($pendingApprovalCount === 1 ? '' : 's') .
                ' waiting for approval.';
            if (!empty($actionReports)) {
                $reportRefs = [];
                foreach ($actionReports as $report) {
                    $reportNo = trim((string) ($report['report_no'] ?? ''));
                    if ($reportNo !== '') {
                        $reportRefs[] = $reportNo;
                    }
                }

                if (!empty($reportRefs)) {
                    $actionBody =
                        'Please review report ' . implode(', ', $reportRefs) . ' and decide on approval status.';
                } else {
                    $actionBody = 'Please review pending reports and complete the final approval decision.';
                }
            } else {
                $actionBody = 'Please review pending reports and complete the final approval decision.';
            }
        } elseif ($criticalCount > 0) {
            $actionHeadline = 'No pending approvals right now.';
            $actionBody = 'Great work on approvals. Keep an eye on critical incidents for any urgent updates.';
            $actionCtaHref = app_url('reports.php');
            $actionCtaLabel = 'View Critical Reports →';
        } else {
            $actionHeadline = 'Well done, all pending approvals are complete.';
            $actionBody = 'Everything is up to date. Keep monitoring incoming activity throughout the day.';
            $actionCtaHref = app_url('reports.php');
            $actionCtaLabel = 'View All Reports →';
        }

        $latestActivity = $presidentRecent[0] ?? null;
        ?>

        <div class="col-12 col-lg-3">
            <div class="metric-card metric-accent-info p-3 d-flex flex-column h-100 w-100 text-dark">
                <div class="mb-3 flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-lightbulb-fill fs-5 text-warning"></i>
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">Action Required</h6>
                    </div>

                    <p class="mb-1 fw-bold text-dark" style="font-size: 0.8rem; line-height: 1.3;">
                        <?php echo htmlspecialchars($actionHeadline); ?>
                    </p>
                    <p class="mb-1 text-muted" style="font-size: 0.8rem; line-height: 1.3;">
                        <?php echo htmlspecialchars($actionBody); ?>
                    </p>

                    <a href="<?php echo htmlspecialchars(
                        $actionCtaHref,
                    ); ?>" class="text-xs text-primary fw-bold text-decoration-none"><?php echo htmlspecialchars(
    $actionCtaLabel,
); ?></a>
                </div>

                <div class="mt-auto pt-2 border-top border-dark-subtle">
                    <p class="fw-bold mb-1 text-muted" style="font-size: 0.75rem;">Recent Activity:</p>
                    <?php if (empty($latestActivity)): ?>
                        <p class="mb-0 text-dark opacity-85" style="font-size: 0.7rem; line-height: 1.1;">
                            No recent dashboard activity yet.
                        </p>
                    <?php else: ?>
                        <?php
                        $activityStatus = (string) ($latestActivity['status'] ?? '');
                        $activityIcon =
                            $activityStatus === 'resolved'
                                ? 'bi-check-circle text-success'
                                : 'bi-clock-history text-primary';
                        $submittedByName = trim((string) ($latestActivity['submitted_by_name'] ?? ''));
                        $submittedByLabel = $submittedByName !== '' ? $submittedByName : 'Unknown user';
                        ?>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi <?php echo htmlspecialchars($activityIcon); ?> fs-7"></i>
                            <p class="mb-0 text-dark opacity-85" style="font-size: 0.7rem; line-height: 1.1;">
                                <?php echo htmlspecialchars((string) ($latestActivity['report_no'] ?? 'Report')); ?>:
                                <?php echo htmlspecialchars(report_status_label($activityStatus)); ?>
                                by <span class="fw-bold"><?php echo htmlspecialchars($submittedByLabel); ?></span>
                                (<?php echo htmlspecialchars(
                                    date('M d, h:i A', strtotime((string) ($latestActivity['submitted_at'] ?? 'now'))),
                                ); ?>)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-3">
            <div id="reportSummaryCard" class="metric-card metric-accent-destructive summary-report-container p-3 d-flex flex-column h-100 w-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="summary-mode-toggle" role="tablist" aria-label="Summary format">
                        <button type="button" id="modePdfBtn" class="summary-mode-btn active" onclick="switchMode('pdf')" aria-pressed="true">PDF</button>
                        <button type="button" id="modeExcelBtn" class="summary-mode-btn" onclick="switchMode('excel')" aria-pressed="false">Excel</button>
                    </div>
                    <span class="summary-icon-badge">
                        <i id="summaryIconInner" class="bi bi-filetype-pdf fs-5" aria-hidden="true"></i>
                    </span>
                </div>

                <div class="mb-2 flex-grow-1">
                    <h6 id="summaryTitle" class="fw-bold text-dark summary-card-title">Daily PDF Summary</h6>
                    <p id="summaryDateLine" class="summary-date-line">
                        Coverage Date: <span class="fw-bold text-dark"><?php echo htmlspecialchars(
                            $displayReportDate,
                        ); ?></span>
                    </p>
                    <p id="summaryHelpText" class="summary-help">
                        Summary of verified reports from <?php echo htmlspecialchars(
                            $displayReportDate,
                        ); ?> for next-day review (<?php echo htmlspecialchars($currentSummaryDate); ?>).
                    </p>
                </div>

                <div class="row g-2 mb-3 summary-actions">
                    <div class="col-6" id="summaryViewCol">
                        <a id="btnView" href="api/daily_summary_pdf.php?date=<?php echo $yesterdayDate; ?>&action=view" target="_blank" class="btn btn-summary-outline btn-sm w-100 py-1">
                            <i class="bi bi-eye"></i> View PDF
                        </a>
                    </div>
                    <div class="col-6" id="summaryDownloadCol">
                        <a id="btnDownload" href="api/daily_summary_pdf.php?date=<?php echo $yesterdayDate; ?>&action=download" download class="btn btn-summary-solid btn-sm w-100 py-1">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                    </div>
                </div>

                <div class="mt-auto pt-2 border-top border-dark-subtle">
                    <p id="summaryFooterNote" class="mb-0 text-dark opacity-75" style="font-size: 0.65rem; line-height: 1.2;">
                        <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars(
                            (string) $count,
                        ); ?> verified report(s) included in this cycle.
                    </p>
                </div>
            </div>
        </div>
    </div> 
            
        <div class="row g-3">

            <!-- Recent Reports col-8 -->
            <div class="col-lg-8">
                <div class="table-container table-card" style="--table-accent: var(--primary)">
                    <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h3 class="font-semibold text-foreground">Recent Reports</h3>
                            <p class="text-xs text-muted-foreground">Latest 5 submissions across all departments</p>
                        </div>
                        <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(
                            app_url('reports.php'),
                        ); ?>">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Department</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($presidentRecent)): ?>
                                    <tr><td colspan="5" class="text-center text-muted-foreground py-4">No reports found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($presidentRecent as $r): ?>
                                        <?php
                                        $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                                        $sevBadge = match ($sevRaw) {
                                            'critical' => 'badge--destructive',
                                            'high' => 'badge--warning',
                                            'medium' => 'badge--info',
                                            default => 'badge--muted',
                                        };
                                        $statusRaw = (string) ($r['status'] ?? '');
                                        $statusBadge = 'badge--muted';
                                        if ($statusRaw === 'submitted_to_ga_manager') {
                                            $statusBadge = 'badge--warning';
                                        } elseif (
                                            in_array($statusRaw, ['under_department_fix', 'sent_to_department'], true)
                                        ) {
                                            $statusBadge = 'badge--info';
                                        } elseif ($statusRaw === 'for_security_final_check') {
                                            $statusBadge = 'badge--primary';
                                        } elseif ($statusRaw === 'returned_to_department') {
                                            $statusBadge = 'badge--destructive';
                                        } elseif ($statusRaw === 'resolved') {
                                            $statusBadge = 'badge--success';
                                        }
                                        ?>
                                        <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
                                            $r['report_no'],
                                        ); ?>')">
                                            <td class="font-medium text-truncate" style="max-width:200px;"><?php echo htmlspecialchars(
                                                $r['subject'],
                                            ); ?></td>
                                            <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                                $r['department_name'],
                                            ); ?></td>
                                            <td><span class="badge <?php echo $sevBadge; ?>"><?php echo htmlspecialchars(
    severity_label($r['severity']),
); ?></span></td>
                                            <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars(
    report_status_label($r['status']),
); ?></span></td>
                                            <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                                date('M d, Y', strtotime($r['submitted_at'])),
                                            ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Approval col-4 -->
            <div class="col-lg-4">
                <div class="table-container table-card h-100" style="--table-accent: var(--warning)">
                    <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h3 class="font-semibold text-foreground">Pending Approval</h3>
                            <p class="text-xs text-muted-foreground">Awaiting your final decision</p>
                        </div>
                        <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(
                            app_url('ga-manager-approval.php'),
                        ); ?>">View all</a>
                    </div>
                    <?php if (!empty($presidentPending)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Severity</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($presidentPending as $p): ?>
                                        <?php
                                        $sevR = strtolower((string) ($p['severity'] ?? ''));
                                        $pBadge = match ($sevR) {
                                            'critical' => 'badge--destructive',
                                            'high' => 'badge--warning',
                                            'medium' => 'badge--info',
                                            default => 'badge--muted',
                                        };
                                        ?>
                                        <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
                                            $p['report_no'],
                                        ); ?>')">
                                            <td class="font-medium text-truncate" style="max-width:130px;" title="<?php echo htmlspecialchars(
                                                $p['subject'],
                                            ); ?>"><?php echo htmlspecialchars($p['subject']); ?></td>
                                            <td><span class="badge <?php echo $pBadge; ?>"><?php echo htmlspecialchars(
    severity_label($p['severity']),
); ?></span></td>
                                            <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                                date('M d', strtotime($p['submitted_at'])),
                                            ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center" style="min-height: 220px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: var(--success);"></i>
                            <p class="fw-semibold mt-3 mb-1 text-foreground">All Clear</p>
                            <p class="text-xs text-muted-foreground">No reports are currently awaiting your approval.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.row -->

        <!-- Metric List Modal -->
        <div id="metric-modal-overlay" class="modal-overlay" aria-hidden="true">
            <div id="metric-modal" class="report-modal" role="dialog" aria-modal="true" aria-labelledby="metric-modal-title">
                <div class="report-modal-header">
                    <h3 id="metric-modal-title">Metric</h3>
                    <button type="button" class="modal-close-btn" onclick="MetricModal.close()" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="report-modal-body" id="metric-modal-body">
                    <div class="text-sm text-muted-foreground">Loading…</div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="MetricModal.close()">Close</button>
                </div>
            </div>
        </div>

        <script>
        (function () {
            const apiUrl = <?php echo json_encode(app_url('api/president_metric_list.php')); ?>;
            const overlay = document.getElementById('metric-modal-overlay');
            const titleEl = document.getElementById('metric-modal-title');
            const bodyEl = document.getElementById('metric-modal-body');

            function escapeHtml(s) {
                return String(s ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatStatus(status) {
                const s = String(status || '').trim();
                if (!s) return '—';
                return s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }

            function formatSeverity(sev) {
                const s = String(sev || '').trim();
                if (!s) return '—';
                return s.charAt(0).toUpperCase() + s.slice(1);
            }

            function formatDate(dt) {
                if (!dt) return '—';
                const d = new Date(dt);
                if (isNaN(d.getTime())) return String(dt);
                return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
            }

            function render(items, type) {
                if (!Array.isArray(items) || items.length === 0) {
                    bodyEl.innerHTML = '<div class="text-sm text-muted-foreground">No records found.</div>';
                    return;
                }

                const showOverdue = (type === 'overdue');
                let html = '';
                html += '<div class="table-container table-responsive">';
                // Use a specific ID so our CSS targets this table correctly
                html += '<table id="staff-dashboard-table" class="table table-hover align-middle mb-0">'; 
                html += '<thead><tr>';
                html += '<th>Report ID</th>';
                html += '<th>Subject</th>';
                html += '<th>Department</th>';
                html += '<th>Severity</th>';
                html += '<th>Status</th>';
                if (showOverdue) {
                    html += '<th>Due</th>';
                    html += '<th>Days Overdue</th>';
                } else {
                    html += '<th>Submitted</th>';
                }
                html += '</tr></thead>';
                html += '<tbody>';

                for (const r of items) {
                    const reportNo = escapeHtml(r.report_no);
                    html += '<tr class="clickable-row" onclick="ReportModal.open(\'' + reportNo + '\')">';
                    html += '<td class="font-mono text-xs font-medium">' + reportNo + '</td>';
                    
                    // ADDED: subject-cell class here to keep the subject left-aligned
                    html += '<td class="subject-cell font-medium">' + escapeHtml(r.subject) + '</td>';
                    
                    html += '<td class="text-muted-foreground">' + escapeHtml(r.department) + '</td>';
                    html += '<td class="text-muted-foreground">' + escapeHtml(formatSeverity(r.severity)) + '</td>';
                    html += '<td class="text-muted-foreground">' + escapeHtml(formatStatus(r.status)) + '</td>';
                    
                    if (showOverdue) {
                        html += '<td class="text-muted-foreground text-xs">' + escapeHtml(formatDate(r.fix_due_date)) + '</td>';
                        html += '<td class="text-muted-foreground">' + escapeHtml(r.days_overdue ?? 0) + '</td>';
                    } else {
                        html += '<td class="text-muted-foreground text-xs">' + escapeHtml(formatDate(r.submitted_at)) + '</td>';
                    }
                    html += '</tr>';
                }

                html += '</tbody></table></div>';
                bodyEl.innerHTML = html;
            }

            window.MetricModal = {
                open(type, title) {
                    titleEl.textContent = title || 'Metric';
                    bodyEl.innerHTML = '<div class="text-sm text-muted-foreground">Loading…</div>';
                    overlay.classList.add('active');
                    overlay.setAttribute('aria-hidden', 'false');

                    const pageUrl = new URL(window.location.href);
                    const url = new URL(apiUrl, window.location.origin);
                    url.searchParams.set('type', String(type || ''));
                    const building = pageUrl.searchParams.get('building');
                    if (building) url.searchParams.set('building', building);

                    fetch(url.toString(), { credentials: 'same-origin' })
                        .then(r => r.json().then(j => ({ ok: r.ok, status: r.status, json: j })))
                        .then(({ ok, json }) => {
                            if (!ok) throw new Error(json && json.error ? json.error : 'Request failed');
                            render(json.items || [], json.type || type);
                        })
                        .catch(err => {
                            bodyEl.innerHTML = '<div class="alert alert-danger alert-error">' + escapeHtml(err.message || 'Failed to load data') + '</div>';
                        });
                },
                close() {
                    overlay.classList.remove('active');
                    overlay.setAttribute('aria-hidden', 'true');
                }
            };

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) window.MetricModal.close();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('active')) window.MetricModal.close();
            });

            document.querySelectorAll('.metric-card[data-metric]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    window.MetricModal.open(btn.getAttribute('data-metric'), btn.getAttribute('data-title'));
                });
            });
        })();
        </script>
        <?php else: ?>

        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-lg-4">
            <a class="metric-card metric-card-split metric-accent-warning no-underline w-100" href="<?php echo htmlspecialchars(
                app_url('ga-staff-review.php'),
            ); ?>">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Waiting for Review</p>
                        <p class="text-xs text-muted-foreground">Submitted by Security</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) ($gaStaffCounts[
                        'waiting'
                    ] ?? 0); ?></div>
                </div>
            </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
            <a class="metric-card metric-card-split metric-accent-destructive no-underline w-100" href="<?php echo htmlspecialchars(
                app_url('returned-reports.php'),
            ); ?>">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Returned by President</p>
                        <p class="text-xs text-muted-foreground">Edit and resubmit</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) ($gaStaffCounts[
                        'returned'
                    ] ?? 0); ?></div>
                </div>
            </a>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
            <a class="metric-card metric-card-split metric-accent-info no-underline w-100" href="<?php echo htmlspecialchars(
                app_url('reports.php'),
            ); ?>">
                <div class="metric-card-left">
                    <div class="metric-card-icon" aria-hidden="true">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="metric-card-text">
                        <p class="text-sm fw-semibold text-foreground">Forwarded to President</p>
                        <p class="text-xs text-muted-foreground">Sent for final approval</p>
                    </div>
                </div>
                <div class="metric-card-right">
                    <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) ($gaStaffCounts[
                        'forwarded'
                    ] ?? 0); ?></div>
                </div>
            </a>
            </div>
        </div>

        <div class="row g-3">

            <!-- Pending Review col-8 -->
            <div class="col-lg-8">
                <div class="table-container table-card" style="--table-accent: var(--warning)">
                    <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h3 class="font-semibold text-foreground">Reports Waiting for Review</h3>
                            <p class="text-xs text-muted-foreground">Latest reports submitted by Security</p>
                        </div>
                        <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(
                            app_url('ga-staff-review.php'),
                        ); ?>">Open review queue</a>
                    </div>
                    <?php if (!empty($gaStaffWaiting)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Department</th>
                                        <th>Severity</th>
                                        <th>Date Submitted</th>
                                        <th style="width:100px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gaStaffWaiting as $r): ?>
                                        <?php
                                        $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                                        $sevBadge = match ($sevRaw) {
                                            'critical' => 'badge--destructive',
                                            'high' => 'badge--warning',
                                            'medium' => 'badge--info',
                                            default => 'badge--muted',
                                        };
                                        ?>
                                        <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
                                            $r['report_no'],
                                        ); ?>')">
                                            <td class="font-medium text-truncate" style="max-width:200px;"><?php echo htmlspecialchars(
                                                $r['subject'],
                                            ); ?></td>
                                            <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                                $r['department_name'],
                                            ); ?></td>
                                            <td><span class="badge <?php echo $sevBadge; ?>"><?php echo htmlspecialchars(
    severity_label((string) $r['severity']),
); ?></span></td>
                                            <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                                date('M d, Y', strtotime($r['submitted_at'])),
                                            ); ?></td>
                                            <td onclick="event.stopPropagation();">
                                                <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(
                                                    app_url('ga-staff-review.php'),
                                                ); ?>">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center" style="min-height: 220px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: var(--success);"></i>
                            <p class="fw-semibold mt-3 mb-1 text-foreground">All Caught Up!</p>
                            <p class="text-xs text-muted-foreground">No reports are currently waiting for your review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GA Pending Reports col-4 -->
            <div class="col-lg-4">
                <div class="table-container table-card h-100" style="--table-accent: var(--warning)">
                    <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <h3 class="font-semibold text-foreground">GA Pending Reports</h3>
                            <p class="text-xs text-muted-foreground">Awaiting your review &amp; forward to President</p>
                        </div>
                        <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(
                            app_url('ga-staff-review.php'),
                        ); ?>">View all</a>
                    </div>
                    <?php if (!empty($gaStaffWaiting)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Severity</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gaStaffWaiting as $r): ?>
                                        <?php
                                        $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                                        $rBadge = match ($sevRaw) {
                                            'critical' => 'badge--destructive',
                                            'high' => 'badge--warning',
                                            'medium' => 'badge--info',
                                            default => 'badge--muted',
                                        };
                                        ?>
                                        <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
                                            $r['report_no'],
                                        ); ?>')">
                                            <td class="font-medium text-truncate" style="max-width:130px;" title="<?php echo htmlspecialchars(
                                                $r['subject'],
                                            ); ?>"><?php echo htmlspecialchars($r['subject']); ?></td>
                                            <td><span class="badge <?php echo $rBadge; ?>"><?php echo htmlspecialchars(
    severity_label((string) $r['severity']),
); ?></span></td>
                                            <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                                date('M d', strtotime($r['submitted_at'])),
                                            ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center" style="min-height: 220px;">
                            <i class="bi bi-send-check-fill" style="font-size: 3rem; color: var(--success);"></i>
                            <p class="fw-semibold mt-3 mb-1 text-foreground">All Forwarded!</p>
                            <p class="text-xs text-muted-foreground">No reports are currently awaiting your review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.row -->

        <?php endif; ?>
    </div>
</main>

<script>
    window.NIDEC_ROLE = '<?php echo htmlspecialchars($userRole ?? ''); ?>';
    (function () {
        const el = document.getElementById('building-filter');
        if (!el) return;
        el.addEventListener('change', () => {
            const val = el.value;
            const url = new URL(window.location.href);
            if (val === 'all') url.searchParams.delete('building');
            else url.searchParams.set('building', val);
            window.location.href = url.toString();
        });
    })();

   function switchMode(mode) {
    const card = document.getElementById('reportSummaryCard');
    const icon = document.getElementById('summaryIconInner');
    const modePdfBtn = document.getElementById('modePdfBtn');
    const modeExcelBtn = document.getElementById('modeExcelBtn');
    const btnView = document.getElementById('btnView');
    const viewCol = document.getElementById('summaryViewCol');
    const downloadCol = document.getElementById('summaryDownloadCol');
    const btnDownload = document.getElementById('btnDownload');
    const title = document.getElementById('summaryTitle');
    const dateLine = document.getElementById('summaryDateLine');
    const helpText = document.getElementById('summaryHelpText');
    const footerNote = document.getElementById('summaryFooterNote');

    const reportDate = <?php echo json_encode($yesterdayDate); ?>;
    const reportDisplayDate = <?php echo json_encode($displayReportDate); ?>;
    const currentSummaryDateJs = <?php echo json_encode($currentSummaryDate); ?>;
    const nextReportDateJs = <?php echo json_encode($nextReportDate); ?>;
    const verifiedCount = <?php echo (int) $count; ?>;
    const pdfEndpoint = <?php echo json_encode(app_url('api/daily_summary_pdf.php')); ?>;
    const excelEndpoint = <?php echo json_encode(app_url('api/daily_summary_excel.php')); ?>;

    if (mode === 'pdf') {
        card.classList.remove('metric-accent-success');
        card.classList.add('metric-accent-destructive');
        icon.className = 'bi bi-filetype-pdf fs-5';
        title.innerText = 'Daily PDF Summary';
        dateLine.innerHTML = `Coverage Date: <span class="fw-bold text-dark">${reportDisplayDate}</span>`;
        helpText.innerText = `Summary of verified reports from ${reportDisplayDate} for next-day review (${currentSummaryDateJs}).`;
        footerNote.innerHTML = `<i class="bi bi-info-circle"></i> ${verifiedCount} verified report(s) included in this cycle.`;

        modePdfBtn.classList.add('active');
        modePdfBtn.setAttribute('aria-pressed', 'true');
        modeExcelBtn.classList.remove('active');
        modeExcelBtn.setAttribute('aria-pressed', 'false');

        viewCol.classList.remove('d-none');
        downloadCol.classList.remove('col-12');
        downloadCol.classList.add('col-6');

        btnView.href = `${pdfEndpoint}?date=${reportDate}&action=view`;
        btnView.target = '_blank';
        btnView.innerHTML = '<i class="bi bi-eye"></i> View PDF';

        btnDownload.href = `${pdfEndpoint}?date=${reportDate}&action=download`;
        btnDownload.innerHTML = '<i class="bi bi-download"></i> Download PDF';
    } else {
        card.classList.remove('metric-accent-destructive');
        card.classList.add('metric-accent-success');
        icon.className = 'bi bi-file-earmark-spreadsheet-fill fs-5';
        title.innerText = 'Daily Excel Summary';
        dateLine.innerHTML = `Coverage Date: <span class="fw-bold text-dark">${reportDisplayDate}</span>`;
        helpText.innerText = `Excel format of ${reportDisplayDate} summary for next-day review (${currentSummaryDateJs}).`;
        footerNote.innerHTML = `<i class="bi bi-info-circle"></i> Download-ready spreadsheet for reporting.`;

        modeExcelBtn.classList.add('active');
        modeExcelBtn.setAttribute('aria-pressed', 'true');
        modePdfBtn.classList.remove('active');
        modePdfBtn.setAttribute('aria-pressed', 'false');

        viewCol.classList.add('d-none');
        downloadCol.classList.remove('col-6');
        downloadCol.classList.add('col-12');

        btnDownload.href = `${excelEndpoint}?date=${reportDate}&action=download`;
        btnDownload.innerHTML = '<i class="bi bi-download"></i> Download Excel';
    }
}

switchMode('pdf');


</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
