

<style>
/* ==========================================================================
   1. ROOT & ANIMATIONS
   ========================================================================== */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}

/* ==========================================================================
   2. PENDING REPORT CARDS (Main List)
   ========================================================================== */
.ga-pending-card {
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1.5px solid rgba(148, 163, 184, 0.45);
    background: var(--card);
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.ga-pending-card.ga-sev-low {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.32) 0%, rgba(52, 211, 153, 0.14) 52%, rgba(236, 253, 245, 0.96) 100%);
    border-color: rgba(5, 150, 105, 0.72);
}

.ga-pending-card.ga-sev-medium {
    background: linear-gradient(135deg, rgba(20, 184, 166, 0.16) 0%, rgba(20, 184, 166, 0.05) 55%, rgba(255, 255, 255, 0.95) 100%);
    border-color: rgba(13, 148, 136, 0.55);
}

.ga-pending-card.ga-sev-high {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.28) 0%, rgba(245, 158, 11, 0.10) 55%, rgba(255, 255, 255, 0.95) 100%);
    border-color: rgba(217, 119, 6, 0.7);
}

.ga-pending-card.ga-sev-critical {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.28) 0%, rgba(220, 38, 38, 0.10) 55%, rgba(255, 255, 255, 0.95) 100%);
    border-color: rgba(185, 28, 28, 0.7);
}

.ga-pending-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
}

.ga-pending-card.ga-sev-low:hover {
    border-color: rgba(4, 120, 87, 0.84);
}

.ga-pending-card.ga-sev-medium:hover {
    border-color: rgba(13, 148, 136, 0.55);
}

.ga-pending-card.ga-sev-high:hover {
    border-color: rgba(217, 119, 6, 0.65);
}

.ga-pending-card.ga-sev-critical:hover {
    border-color: rgba(185, 28, 28, 0.65);
}

.ga-pending-card .ga-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.ga-pending-card .ga-reportno {
    font-family: var(--font-mono);
    font-size: 13px;
    font-weight: 700;
    color: var(--muted-foreground);
    letter-spacing: -0.01em;
}

.ga-pending-card .ga-title {
    font-size: 1.125rem;
    font-weight: 750;
    color: var(--foreground);
    line-height: 1.3;
    margin-bottom: 12px;
}

.ga-pending-card .ga-info-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-foreground);
    margin-bottom: 4px;
}

.ga-pending-card .ga-info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--foreground);
}

.ga-pending-card .ga-summary {
    font-size: 14px;
    line-height: 1.6;
    color: var(--muted-foreground);
    margin-top: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ga-pending-card .ga-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.ga-pending-card .ga-bottom-row {
    display: grid;
    grid-template-columns: 7fr 3fr;
    gap: 12px;
    align-items: end;
}

.ga-pending-card .ga-actions .btn {
    min-width: 96px;
}

.ga-pending-card .ga-divider {
    height: 1px;
    background: rgba(100, 116, 139, 0.32);
    margin: 1rem 0;
    width: 100%;
}

.ga-pending-card .badge--warning {
    color: #6b4a00 !important;
    font-weight: 700;
}

.ga-filter-wrap {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}

.ga-filter-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: hsl(var(--muted-foreground));
    font-weight: 700;
}

.ga-filter-wrap .form-select,
.ga-filter-wrap .form-control,
.ga-filter-wrap .form-select-sm,
.ga-filter-wrap .form-control-sm {
    height: 38px;
    min-height: 38px;
    font-size: 14px;
    padding: 8px 12px;
}

.ga-filter-actions-col {
    display: flex;
    align-items: flex-end;
}

#clear-filters {
    height: 38px;
    min-height: 38px;
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
    font-weight: 600;
}

#clear-filters:hover,
#clear-filters:focus {
    background-color: #bb2d3b;
    border-color: #b02a37;
    color: #fff;
}

.ga-filter-summary-right {
    margin-top: 8px;
    text-align: right;
    font-size: 12px;
    color: hsl(var(--muted-foreground));
}

.ga-btn-return {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #fff;
}

.ga-btn-return:hover,
.ga-btn-return:focus {
    background-color: #d97706;
    border-color: #d97706;
    color: #fff;
}

@media (max-width: 991.98px) {
    .ga-pending-card .ga-bottom-row {
        grid-template-columns: 1fr;
    }

    .ga-pending-card .ga-actions {
        justify-content: flex-start;
    }
}

/* ==========================================================================
   3. GLASSMORPHISM OVERLAY (Blur only the background)
   ========================================================================== */
.modal-overlay {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.65); /* Sophisticated dark tint */
    
    /* The Glassmorphism effect happens ONLY here */
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 1.5rem;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* ==========================================================================
   4. SOLID MODAL CONTAINERS (No blur on the card itself)
   ========================================================================== */
.report-modal, 
.confirm-modal-content {
    background: #ffffff; /* Pure solid background from your photos */
    width: 100%;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    position: relative;
    transform: translateY(20px) scale(0.98);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-overlay.active .report-modal,
.modal-overlay.active .confirm-modal-content {
    transform: translateY(0) scale(1);
}

/* ==========================================================================
   5. DETAILED REPORT MODAL INTERNALS (image_eb5a41.png)
   ========================================================================== */
.report-modal {
    max-width: 850px;
    max-height: 90vh;
}

.report-modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.report-modal-header h3 {
    font-size: 1.25rem;
    font-weight: 800;
    margin: 0;
    color: var(--foreground);
}

.report-modal-body {
    padding: 2rem;
    overflow-y: auto;
    background: #f1f5f9; /* Subtle light background for body content */
}

/* These are the specific gray boxes seen in your screenshot */
.modal-info-box {
    background: #e2e8f0; 
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.modal-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.modal-section-title {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #64748b;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ==========================================================================
   6. PREMIUM CONFIRMATION MODAL (image_eb5a5f.png)
   ========================================================================== */
.confirm-modal-content {
    max-width: 460px;
    text-align: left;
    padding: 2rem;
}

.confirm-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 1rem;
    font-size: 28px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

.confirm-modal-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.confirm-modal-message {
    font-size: 15px;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.confirm-modal-header {
    position: relative;
}

.confirm-close-btn {
    position: absolute;
    top: 0;
    right: 0;
    border: none;
    background: transparent;
    color: #94a3b8;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    font-size: 16px;
    line-height: 1;
}

.confirm-close-btn:hover {
    background: #f1f5f9;
    color: #334155;
}

.confirm-action-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 0.3rem 0.6rem;
    margin-bottom: 0.75rem;
    background: #e2e8f0;
    color: #334155;
}

.confirm-action-chip.return {
    background: #fee2e2;
    color: #991b1b;
}

.confirm-action-chip.forward {
    background: #dcfce7;
    color: #166534;
}

.confirm-modal-body {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem;
}

.confirm-modal-notes {
    width: 100%;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem;
    font-size: 14px;
    transition: all 0.2s;
    margin-top: 0.5rem;
}

.confirm-modal-notes:focus {
    outline: none;
    border-color: var(--accent);
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(var(--accent-rgb), 0.1);
}

.confirm-modal-notes.is-invalid {
    border-color: #dc2626;
}

.confirm-textarea-label {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}

.confirm-modal-footer {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
    flex-wrap: wrap;
}

/* ==========================================================================
   7. ACTION BUTTONS (Matched to Photo Colors)
   ========================================================================== */
.btn-confirm-return {
    background: #ef4444 !important;
    color: #ffffff !important;
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 12px;
    border: none;
}

.btn-confirm-forward {
    background: #10b981 !important;
    color: #ffffff !important;
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 12px;
    border: none;
}

.btn-modern-cancel {
    background: #e2e8f0;
    color: #334155;
    font-weight: 600;
    padding: 10px 18px;
    border: 1px solid transparent;
    border-radius: 10px;
}

.btn-modern-cancel:hover {
    background: #cbd5e1;
    color: #1e293b;
}

.btn-confirm-action {
    color: #ffffff;
    font-weight: 700;
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
}

.btn-confirm-action.return {
    background: #dc2626;
}

.btn-confirm-action.forward {
    background: #16a34a;
}

.hidden { display: none !important; }
</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-eye-fill me-2 text-primary"></i>GA Staff Review</h1>
                <p class="text-sm text-muted-foreground mb-0">Review and approve reports to send to GA Manager</p>
            </div>
        </div>

        <div class="ga-filter-wrap">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="search-filter" class="ga-filter-label mb-1">Search</label>
                    <input id="search-filter" type="text" class="form-control form-control-sm" placeholder="Report no, subject, location...">
                </div>
                <div class="col-8 col-md-2">
                    <label for="building-filter" class="ga-filter-label mb-1">Entity</label>
                    <select id="building-filter" class="form-select form-select-sm">
                        <option value="all" <?php echo ($selectedBuilding ?? 'all') === 'all'
                            ? 'selected'
                            : ''; ?>>All</option>
                        <option value="NCFL" <?php echo ($selectedBuilding ?? '') === 'NCFL'
                            ? 'selected'
                            : ''; ?>>NCFL</option>
                        <option value="NPFL" <?php echo ($selectedBuilding ?? '') === 'NPFL'
                            ? 'selected'
                            : ''; ?>>NPFL</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="severity-filter" class="ga-filter-label mb-1">Severity</label>
                    <select id="severity-filter" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="department-filter" class="ga-filter-label mb-1">Department</label>
                    <select id="department-filter" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <?php foreach ($departments ?? [] as $departmentRow): ?>
                            <?php $deptName = trim((string) ($departmentRow['name'] ?? '')); ?>
                            <?php if ($deptName === '') {
                                continue;
                            } ?>
                            <option value="<?php echo htmlspecialchars(
                                strtolower($deptName),
                            ); ?>"><?php echo htmlspecialchars($deptName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2 ga-filter-actions-col">
                    <button id="clear-filters" type="button" class="btn btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
            </div>
            <div id="ga-filter-summary" class="ga-filter-summary-right">Showing all pending reports</div>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?php echo ($flashType ?? '') === 'error' ? 'danger' : 'success'; ?> mb-4">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted-foreground">No reports waiting for review.</div>
            </div>
        <?php else: ?>
            <?php foreach ($pending as $r): ?>
                <?php
                $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                $sevBadge =
                    $sevRaw === 'critical'
                        ? 'badge--destructive'
                        : ($sevRaw === 'high'
                            ? 'badge--warning'
                            : ($sevRaw === 'medium'
                                ? 'badge--info'
                                : 'badge--success'));
                $reportNo = (string) ($r['report_no'] ?? '');

                $details = trim((string) ($r['details'] ?? ''));
                $summary = strlen($details) > 220 ? substr($details, 0, 220) . '…' : ($details ?: '—');

                $severityClass = 'ga-sev-low';
                if ($sevRaw === 'critical') {
                    $severityClass = 'ga-sev-critical';
                } elseif ($sevRaw === 'high') {
                    $severityClass = 'ga-sev-high';
                } elseif ($sevRaw === 'medium') {
                    $severityClass = 'ga-sev-medium';
                }

                $departmentName = (string) ($r['department_name'] ?? '');
                $entityName = trim((string) ($r['building'] ?? ''));
                if ($entityName === '') {
                    $locationUpper = strtoupper((string) ($r['location'] ?? ''));
                    if (str_contains($locationUpper, 'NCFL')) {
                        $entityName = 'NCFL';
                    } elseif (str_contains($locationUpper, 'NPFL')) {
                        $entityName = 'NPFL';
                    }
                }

                $searchBlob = strtolower(
                    trim(
                        (string) ($r['report_no'] ?? '') .
                            ' ' .
                            (string) ($r['subject'] ?? '') .
                            ' ' .
                            (string) ($r['category'] ?? '') .
                            ' ' .
                            (string) ($r['location'] ?? '') .
                            ' ' .
                            $departmentName .
                            ' ' .
                            $entityName,
                    ),
                );
                ?>

                <div class="card shadow-sm mb-3 ga-pending-card <?php echo $severityClass; ?>"
                     data-severity="<?php echo htmlspecialchars($sevRaw !== '' ? $sevRaw : 'low'); ?>"
                     data-entity="<?php echo htmlspecialchars(strtolower($entityName)); ?>"
                     data-department="<?php echo htmlspecialchars(strtolower($departmentName)); ?>"
                     data-search="<?php echo htmlspecialchars($searchBlob); ?>"
                     onclick="GaStaffReview.open('<?php echo htmlspecialchars($reportNo); ?>')">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                            <div>
                                <div class="ga-meta">
                                    <span class="ga-reportno"><?php echo htmlspecialchars($reportNo); ?></span>
                                    <span class="badge <?php echo $sevBadge; ?>"><?php echo htmlspecialchars(
    $r['severity'] ?? 'N/A',
); ?></span>
                                    <span class="badge badge--warning">Pending Review</span>
                                </div>
                                <div class="ga-title"><?php echo htmlspecialchars($r['subject'] ?? 'Untitled'); ?></div>
                            </div>

                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Category</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars($r['category'] ?? '—'); ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Location</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars($r['location'] ?? '—'); ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Department</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars(
                                    $r['department_name'] ?? '—',
                                ); ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Submitted</div>
                                <div class="ga-info-value"><?php echo !empty($r['submitted_at'])
                                    ? date('m/d/Y', strtotime($r['submitted_at']))
                                    : '—'; ?></div>
                            </div>
                        </div>

                        <div class="ga-divider" aria-hidden="true"></div>
                        <div class="ga-bottom-row">
                            <p class="ga-summary mb-0"><?php echo htmlspecialchars($summary); ?></p>

                            <div class="ga-actions">
                                <button type="button" class="btn ga-btn-return btn-sm" onclick="event.stopPropagation(); GaQuickAction.confirm('<?php echo htmlspecialchars(
                                    $reportNo,
                                ); ?>', 'return');">
                                    <i class="bi bi-arrow-left me-1"></i> Return
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); GaQuickAction.confirm('<?php echo htmlspecialchars(
                                    $reportNo,
                                ); ?>', 'forward');">
                                    <i class="bi bi-send me-1"></i> Approve
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div id="ga-empty-filter" class="card shadow-sm hidden">
            <div class="card-body text-center text-muted-foreground py-5">No reports match the selected filters.</div>
        </div>
    </div>
</main>

<form method="POST" id="ga-quick-action-form" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
    <input type="hidden" name="report_no" id="ga-quick-report-no" value="" />
    <input type="hidden" name="notes" id="ga-quick-notes" value="" />
    <input type="hidden" name="action" id="ga-quick-action" value="" />
</form>

<div id="ga-review-modal-overlay" class="modal-overlay hidden">
    <div class="report-modal">
        <div class="report-modal-header">
            <h3 id="ga-review-modal-title">Review Report</h3>
            <button class="modal-close-btn" type="button" onclick="GaStaffReview.close()"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="report-modal-body">
            <div id="ga-review-modal-body">
                </div>
            <div class="modal-section mt-3">
                <div class="modal-section-title">Internal Notes</div>
                <textarea id="ga-review-notes" rows="3" class="form-control" placeholder="Add optional internal review notes..."></textarea>
            </div>
        </div>
        <div class="report-modal-footer">
            <button type="button" class="btn btn-destructive btn-sm" onclick="GaStaffReview.beforeSubmit({preventDefault:()=>{}, submitter:{value:'return'}})">Return to Security</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="GaStaffReview.beforeSubmit({preventDefault:()=>{}, submitter:{value:'forward'}})">Forward to Manager</button>
        </div>
    </div>
</div>

<div id="confirm-modal-overlay" class="modal-overlay hidden">
    <div class="confirm-modal-content">
        <div class="confirm-modal-header">
            <button type="button" class="confirm-close-btn" onclick="CustomConfirm.close()" aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
            <div id="confirm-action-chip" class="confirm-action-chip"><i id="confirm-action-chip-icon" class="bi"></i><span id="confirm-action-chip-text">Action</span></div>
            <div id="confirm-icon-box" class="confirm-icon-circle">
                <i id="confirm-icon" class="bi"></i>
            </div>
            <h3 id="confirm-title" class="confirm-modal-title">Confirm Action</h3>
            <p id="confirm-msg" class="confirm-modal-message">Are you sure you want to proceed?</p>
        </div>
        <div class="confirm-modal-body">
            <label id="confirm-notes-label" class="confirm-textarea-label">Reason / Feedback (Required for Return)</label>
            <textarea id="confirm-notes" class="confirm-modal-notes" rows="4" placeholder="Type your message for the security team here..."></textarea>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn-modern-cancel" onclick="CustomConfirm.close()">Cancel</button>
            <button type="button" id="confirm-submit-btn" class="btn-confirm-action">Confirm</button>
        </div>
    </div>
</div>
            <!-- <div id="confirm-modal-overlay" class="modal-overlay hidden" style="z-index: 9999;">
                <div class="report-modal" style="max-width: 400px; min-height: auto; margin-top: 15vh;">
                    <div class="report-modal-header">
                        <h3 id="confirm-modal-title">Confirm Action</h3>
                    </div>
                    <div class="report-modal-body">
                        <p id="confirm-modal-message" class="text-sm text-foreground">Are you sure you want to proceed?</p>
                    </div>
                    <div class="report-modal-footer" style="padding: 1rem; gap: 0.5rem; display: flex; justify-content: flex-end;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="CustomConfirm.close()">Cancel</button>
                        <button type="button" id="confirm-submit-btn" class="btn btn-primary btn-sm">Yes, Proceed</button>
                    </div>
                </div>
            </div>
            <div class="report-modal-footer">
                <button type="submit" class="btn btn-destructive btn-sm" name="action" value="return">Return to Security</button>
                <button type="submit" class="btn btn-primary btn-sm" name="action" value="forward">Forward to GA Manager</button>
            </div>
        </form>
    </div>
</div> -->





<script>
/**
 * GA Staff Review - Main Logic
 * Handles opening the detailed report modal, fetching data from the API,
 * and rendering the full content including images and attachments.
 */
const GaStaffReview = {
    overlay: null,
    body: null,
    title: null,
    reportNoInput: null,
    currentReportNo: null,

    init() {
        this.overlay = document.getElementById('ga-review-modal-overlay');
        this.body = document.getElementById('ga-review-modal-body');
        this.title = document.getElementById('ga-review-modal-title');
        this.reportNoInput = document.getElementById('ga-review-report-no');

        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) this.close();
            });
        }
    },

    async open(reportNo) {
        if (!this.overlay) this.init();

        this.currentReportNo = reportNo;
        // Reset and show loading state
        document.getElementById('ga-review-notes').value = '';
        this.title.textContent = `Review Report: ${reportNo}`;
        this.body.innerHTML = `
            <div class="p-5 text-center">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="text-sm text-muted-foreground">Fetching report details...</div>
            </div>
        `;
        
        this.overlay.classList.add('active');
        this.overlay.classList.remove('hidden');

        try {
            const res = await fetch(`api/report.php?id=${encodeURIComponent(reportNo)}`, { 
                credentials: 'same-origin' 
            });
            const data = await res.json();
            
            if (!res.ok) throw new Error(data && data.error ? data.error : 'Failed to load report');
            
            // Full render with all details
            this.render(data);
        } catch (err) {
            this.body.innerHTML = `
                <div class="p-4 m-3 rounded bg-destructive/10 text-destructive text-sm border border-destructive/20">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    ${String(err.message || err)}
                </div>
            `;
        }
    },

    close() {
        if (!this.overlay) return;
        this.overlay.classList.remove('active');
        this.overlay.classList.add('hidden');
    },

    // Handles form submission from within the Full Review Modal
    beforeSubmit(e) {
        e.preventDefault();
        const action = (e.submitter && e.submitter.value) ? e.submitter.value : '';
        const notes = document.getElementById('ga-review-notes').value;
        
        // Redirect to the Premium Confirmation Modal
        GaQuickAction.confirm(this.currentReportNo, action, notes);
        return false;
    },

    esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    },

    render(r) {
        const sev = String(r.severity || '').toLowerCase();
        const sevBadge = sev === 'critical' ? 'badge--destructive' : 
                         sev === 'high' ? 'badge--warning' : 
                         sev === 'medium' ? 'badge--info' : 'badge--muted';

        // 1. Process Attachments
        const attachments = Array.isArray(r.attachments) ? r.attachments : [];
        const attachmentsHtml = attachments.length ? `
            <div class="modal-info-item mt-4">
                <div class="modal-info-label mb-2"><i class="bi bi-paperclip me-1"></i>Attachments</div>
                <div class="d-flex flex-wrap gap-2">
                    ${attachments.map(a => {
                        const href = a.url || a.file_path || '#';
                        const name = a.file_name || 'View Attachment';
                        return `<a class="btn btn-outline btn-sm d-inline-flex align-items-center" href="${this.esc(href)}" target="_blank" rel="noopener">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i> ${this.esc(name)}
                                </a>`;
                    }).join('')}
                </div>
            </div>` : '';

        // 2. Process Evidence Image
        const evidenceHtml = r.evidenceImageUrl ? `
            <div class="modal-info-item mt-4">
                <div class="modal-info-label mb-2"><i class="bi bi-image me-1"></i>Evidence Photo</div>
                <div class="modal-image-container overflow-hidden rounded-lg border">
                    <img src="${this.esc(r.evidenceImageUrl)}" alt="Evidence" style="width:100%; height:auto; display:block;" />
                </div>
            </div>` : '';

        const safe = (v, fallback) => {
            const s = String(v ?? '').trim();
            return s === '' ? (fallback || '—') : this.esc(s);
        };

        // 3. Build Full HTML Output
        this.body.innerHTML = `
            <div class="modal-section">
                <div class="modal-section-title">
                    <i class="bi bi-file-earmark-text me-2"></i>Report Summary
                </div>
                <div class="modal-info-item mb-4">
                    <div class="modal-info-label">Subject</div>
                    <div class="modal-info-value font-bold text-lg">${safe(r.subject, 'N/A')}</div>
                </div>
                <div class="modal-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1.5rem;">
                    <div class="modal-info-item">
                        <div class="modal-info-label">Report ID</div>
                        <div class="modal-info-value font-mono">${safe(r.reportNo || r.id, 'N/A')}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="modal-info-label">Severity</div>
                        <div class="modal-info-value"><span class="badge ${sevBadge}">${safe(r.severity, 'N/A')}</span></div>
                    </div>
                    <div class="modal-info-item">
                        <div class="modal-info-label">Department</div>
                        <div class="modal-info-value">${safe(r.department_name || r.department, 'N/A')}</div>
                    </div>
                    <div class="modal-info-item">
                        <div class="modal-info-label">Location</div>
                        <div class="modal-info-value">${safe(r.location, 'N/A')}</div>
                    </div>
                </div>
            </div>
            <div class="modal-section border-t pt-4">
                <div class="modal-section-title">
                    <i class="bi bi-shield-check me-2"></i>Incident Details
                </div>
                <div class="modal-info-item">
                    <div class="modal-description" style="white-space: pre-wrap; line-height: 1.6;">${safe(r.details, 'No description provided')}</div>
                </div>
                ${evidenceHtml}
                ${attachmentsHtml}
            </div>
        `;
    }
};

/**
 * Quick Action Logic
 * Intermediary that prepares data for the Premium CustomConfirm modal.
 */
const GaQuickAction = {
    confirm(reportNo, action, initialNotes = '') {
        CustomConfirm.show(reportNo, action, initialNotes);
    }
};

/**
 * Premium Confirmation Modal (CustomConfirm)
 * Features Backdrop-blur (via CSS), icons, and dynamic status colors.
 */
const CustomConfirm = {
    overlay: null,
    currentReportNo: null,
    currentAction: null,

    init() {
        this.overlay = document.getElementById('confirm-modal-overlay');
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) this.close();
            });
        }
    },

    show(reportNo, action, initialNotes) {
        if (!this.overlay) this.init();
        
        this.currentReportNo = reportNo;
        this.currentAction = action;

        const iconBox = document.getElementById('confirm-icon-box');
        const icon = document.getElementById('confirm-icon');
        const submitBtn = document.getElementById('confirm-submit-btn');
        const notesInput = document.getElementById('confirm-notes');
        const notesLabel = document.getElementById('confirm-notes-label');
        const titleEl = document.getElementById('confirm-title');
        const msgEl = document.getElementById('confirm-msg');
        const actionChip = document.getElementById('confirm-action-chip');
        const actionChipIcon = document.getElementById('confirm-action-chip-icon');
        const actionChipText = document.getElementById('confirm-action-chip-text');

        notesInput.value = initialNotes;
        notesInput.classList.remove('is-invalid');
        submitBtn.classList.remove('return', 'forward');
        
        // 1. Configure UI based on "Return" vs "Forward"
        if (action === 'return') {
            iconBox.className = "confirm-icon-circle icon-return";
            icon.className = "bi bi-arrow-left-circle-fill";
            actionChip.className = "confirm-action-chip return";
            actionChipIcon.className = "bi bi-arrow-counterclockwise";
            actionChipText.textContent = "Return";
            titleEl.textContent = "Return to Security";
            msgEl.textContent = "This will notify the Security team to re-evaluate or fix the report issues.";
            submitBtn.textContent = "Confirm Return";
            submitBtn.classList.add('return');
            notesLabel.textContent = "Reason for Return (Required)";
            notesInput.placeholder = "Type the reason for returning this report...";
        } else {
            iconBox.className = "confirm-icon-circle icon-forward";
            icon.className = "bi bi-check-circle-fill";
            actionChip.className = "confirm-action-chip forward";
            actionChipIcon.className = "bi bi-check2-circle";
            actionChipText.textContent = "Approval";
            titleEl.textContent = "Forward to President";
            msgEl.textContent = "This will officially move the report to the President's desk for final approval.";
            submitBtn.textContent = "Confirm Approval";
            submitBtn.classList.add('forward');
            notesLabel.textContent = "Remarks (Optional)";
            notesInput.placeholder = "Add optional remarks before forwarding...";
        }

        // 2. Show Modal (CSS handles backdrop-blur)
        this.overlay.classList.add('active');
        this.overlay.classList.remove('hidden');

        // 3. Handle Submit
        submitBtn.onclick = () => {
            const finalNotes = notesInput.value.trim();
            
            // Logic Requirement: Return MUST have a reason
            if (action === 'return' && !finalNotes) {
                notesInput.classList.add('is-invalid');
                alert("Please provide a reason for returning this report.");
                return;
            }

            const hiddenForm = document.getElementById('ga-quick-action-form');
            document.getElementById('ga-quick-report-no').value = reportNo;
            document.getElementById('ga-quick-action').value = action;
            document.getElementById('ga-quick-notes').value = finalNotes;
            
            hiddenForm.submit();
        };
    },

    close() {
        if (!this.overlay) return;
        this.overlay.classList.remove('active');
        this.overlay.classList.add('hidden');
    }
};

/**
 * Event Listeners and Initialization
 */
document.addEventListener('DOMContentLoaded', () => {
    GaStaffReview.init();

    const buildingEl = document.getElementById('building-filter');
    const severityEl = document.getElementById('severity-filter');
    const deptEl = document.getElementById('department-filter');
    const searchEl = document.getElementById('search-filter');
    const clearBtn = document.getElementById('clear-filters');
    const summaryEl = document.getElementById('ga-filter-summary');
    const emptyEl = document.getElementById('ga-empty-filter');
    const cards = Array.from(document.querySelectorAll('.ga-pending-card'));

    if (cards.length && buildingEl && severityEl && deptEl && searchEl && clearBtn && summaryEl && emptyEl) {
        const applyFilters = () => {
            const entity = buildingEl.value.toLowerCase();
            const sev = severityEl.value;
            const dept = deptEl.value;
            const term = searchEl.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach((card) => {
                const cardEntity = card.getAttribute('data-entity') || '';
                const cardSev = card.getAttribute('data-severity') || '';
                const cardDept = card.getAttribute('data-department') || '';
                const cardSearch = card.getAttribute('data-search') || '';

                const matchEntity = entity === 'all' || cardEntity === entity;
                const matchSev = sev === 'all' || cardSev === sev;
                const matchDept = dept === 'all' || cardDept === dept;
                const matchTerm = term === '' || cardSearch.includes(term);

                const show = matchEntity && matchSev && matchDept && matchTerm;
                card.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            emptyEl.classList.toggle('hidden', visible !== 0);
            summaryEl.textContent = `Showing ${visible} pending report${visible === 1 ? '' : 's'}`;
        };

        buildingEl.addEventListener('change', applyFilters);
        severityEl.addEventListener('change', applyFilters);
        deptEl.addEventListener('change', applyFilters);
        searchEl.addEventListener('input', applyFilters);

        clearBtn.addEventListener('click', () => {
            buildingEl.value = 'all';
            severityEl.value = 'all';
            deptEl.value = 'all';
            searchEl.value = '';
            applyFilters();
        });

        applyFilters();
    }

    // Global ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            GaStaffReview.close();
            CustomConfirm.close();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
