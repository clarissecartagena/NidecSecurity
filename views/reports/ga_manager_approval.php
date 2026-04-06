<style>
/* ==========================================================================
    GA Manager Approval Card Layout
    ========================================================================== */

.ga-pending-card {
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid hsl(var(--border));
    background: hsl(var(--card));
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
    border-color: rgba(13, 148, 136, 0.35);
}

.ga-pending-card.ga-sev-high {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.28) 0%, rgba(245, 158, 11, 0.10) 55%, rgba(255, 255, 255, 0.95) 100%);
    border-color: rgba(217, 119, 6, 0.55);
}

.ga-pending-card.ga-sev-critical {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.28) 0%, rgba(220, 38, 38, 0.10) 55%, rgba(255, 255, 255, 0.95) 100%);
    border-color: rgba(185, 28, 28, 0.55);
}

.ga-pending-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px -6px rgba(0, 0, 0, 0.12);
}

.ga-pending-card.ga-sev-low:hover {
    border-color: rgba(5, 150, 105, 0.82);
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
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

.ga-pending-card .ga-reportno {
    font-family: var(--font-mono, monospace);
    font-size: 13px;
    font-weight: 700;
    color: hsl(var(--muted-foreground));
    letter-spacing: -0.01em;
}

.ga-pending-card .ga-title {
    font-size: 1.1rem;
    font-weight: 750;
    color: hsl(var(--foreground));
    line-height: 1.3;
    margin-bottom: 0;
}

.ga-pending-card .ga-info-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: hsl(var(--muted-foreground));
    margin-bottom: 3px;
}

.ga-pending-card .ga-info-value {
    font-size: 13.5px;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.ga-pending-card .ga-reviewer-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    background: hsl(var(--muted));
    border-radius: 20px;
    padding: 2px 10px;
}

.ga-pending-card .ga-bottom-row {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(15, 23, 42, 0.15);
}

.ga-pending-card .ga-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    align-items: stretch;
}

.ga-pending-card .ga-actions .btn {
    min-width: 0;
    width: 100%;
    white-space: nowrap;
}

.ga-pending-card .ga-actions form {
    margin: 0;
    width: 100%;
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
}

.ga-filter-wrap .form-select-sm,
.ga-filter-wrap .form-control-sm {
    padding: 8px 12px;
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

.ga-filter-actions-col {
    display: flex;
    align-items: flex-end;
}

.ga-pending-card .badge--warning {
    color: #6b4a00 !important;
    font-weight: 700;
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
    .ga-filter-summary-right {
        text-align: left;
    }
}

</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-hourglass-split me-2 text-primary"></i>GA Pending Reports</h1>
                <p class="text-sm text-muted-foreground mb-0">Review reports submitted by Security and approve for Department action</p>
            </div>
        </div>

        <div class="ga-filter-wrap">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="search-filter" class="ga-filter-label mb-1">Search</label>
                    <input id="search-filter" type="text" class="form-control form-control-sm" placeholder="Report no, subject, location...">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="building-filter" class="ga-filter-label mb-1">Entity</label>
                    <select id="building-filter" class="form-select form-select-sm">
                        <option value="all"  <?php echo $selectedBuilding === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
                        <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="severity-filter" class="ga-filter-label mb-1">Severity</label>
                    <select id="severity-filter" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
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
                
                <div class="col-6 col-lg-2 ga-filter-actions-col">
                    <button id="clear-filters" type="button" class="btn btn-sm w-100">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
            </div>
            <div id="ga-filter-summary" class="ga-filter-summary-right">Showing all pending reports</div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted-foreground py-5">No reports waiting for your approval.</div>
            </div>
        <?php else: ?>
            <div class="row g-3" id="ga-pending-grid">
            <?php foreach ($pending as $r): ?>
                <?php
                $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                if ($sevRaw === 'critical') {
                    $sevBadge = 'badge--destructive';
                } elseif ($sevRaw === 'high') {
                    $sevBadge = 'badge--warning';
                } elseif ($sevRaw === 'medium') {
                    $sevBadge = 'badge--info';
                } else {
                    $sevBadge = 'badge--success';
                }
                $reportNo = htmlspecialchars($r['report_no'] ?? '');
                $reviewer = trim((string) ($r['ga_staff_reviewer'] ?? ''));
                ?>
                <?php
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
                 <div class="col-12 col-md-6 col-lg-4 ga-pending-col">
                    <div class="card shadow-sm h-100 ga-pending-card <?php echo $severityClass; ?>"
                        data-severity="<?php echo htmlspecialchars($sevRaw !== '' ? $sevRaw : 'low'); ?>"
                        data-entity="<?php echo htmlspecialchars(strtolower($entityName)); ?>"
                        data-department="<?php echo htmlspecialchars(strtolower($departmentName)); ?>"
                        data-search="<?php echo htmlspecialchars($searchBlob); ?>"
                        onclick="ReportModal.open('<?php echo $reportNo; ?>')">
                        <div class="card-body p-3">

                        <!-- Top row: meta -->
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                            <div class="flex-grow-1">
                                <div class="ga-meta">
                                    <span class="ga-reportno"><?php echo $reportNo; ?></span>
                                    <span class="badge <?php echo $sevBadge; ?>"><?php echo htmlspecialchars(
    $r['severity'] ?? 'N/A',
); ?></span>
                                    <span class="badge badge--warning">Pending Approval</span>
                                    <?php if ($reviewer !== ''): ?>
                                        <span class="ga-reviewer-chip">
                                            <i class="bi bi-person-check"></i>
                                            Last updated by <?php echo htmlspecialchars($reviewer); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="ga-title"><?php echo htmlspecialchars($r['subject'] ?? 'Untitled'); ?></div>
                            </div>
                        </div>

                        <!-- Info grid -->
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <div class="ga-info-label">Category</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars(
                                    $r['category'] ?? '---',
                                ); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="ga-info-label">Location</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars(
                                    $r['location'] ?? '---',
                                ); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="ga-info-label">Department</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars(
                                    $r['department_name'] ?? '---',
                                ); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="ga-info-label">Submitted</div>
                                <div class="ga-info-value"><?php echo !empty($r['submitted_at'])
                                    ? date('m/d/Y', strtotime($r['submitted_at']))
                                    : '---'; ?></div>
                            </div>
                        </div>

                        <div class="ga-bottom-row">
                            <div class="ga-actions" onclick="event.stopPropagation();">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
                                        csrf_token(),
                                    ); ?>">
                                    <input type="hidden" name="report_no"  value="<?php echo htmlspecialchars(
                                        $r['report_no'],
                                    ); ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">
                                        <i class="bi bi-check-lg me-1"></i>Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-destructive btn-sm"
                                        data-reject-report-no="<?php echo htmlspecialchars($r['report_no']); ?>"
                                        data-reject-subject="<?php echo htmlspecialchars($r['subject']); ?>">
                                    <i class="bi bi-x-lg me-1"></i>Reject
                                </button>
                                <button type="button" class="btn ga-btn-return btn-sm"
                                        data-return-report-no="<?php echo htmlspecialchars($r['report_no']); ?>"
                                        data-return-subject="<?php echo htmlspecialchars($r['subject']); ?>">
                                    <i class="bi bi-arrow-left me-1"></i>Return
                                </button>
                            </div>
                        </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="ga-empty-filter" class="card shadow-sm hidden">
            <div class="card-body text-center text-muted-foreground py-5">No reports match the selected filters.</div>
        </div>
    </div>
</main>

<div id="ga-action-overlay" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ga-reject-title">
        <div class="modal-header">
            <h2 id="ga-reject-title" class="text-lg font-semibold text-foreground">Action</h2>
            <p id="ga-action-subtitle" class="text-sm text-muted-foreground mt-1">Please provide a reason.</p>
        </div>
        <div class="modal-section" style="margin-bottom: 0.75rem;">
            <div class="text-xs text-muted-foreground" id="ga-reject-meta"></div>
        </div>
        <label class="text-sm font-medium text-foreground" for="ga-reject-reason">Reason</label>
        <textarea id="ga-reject-reason" class="form-control mt-2" placeholder="Type the reason..." maxlength="500" style="resize: vertical; min-height: 100px;" required></textarea>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-sm" id="ga-reject-cancel">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="ga-action-submit">Submit</button>
        </div>
    </div>
</div>

<form id="ga-action-form" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="report_no" value="" />
    <input type="hidden" name="notes" value="" />
</form>

<script>
(() => {
    const overlay = document.getElementById('ga-action-overlay');
    const meta = document.getElementById('ga-reject-meta');
    const reason = document.getElementById('ga-reject-reason');
    const cancelBtn = document.getElementById('ga-reject-cancel');
    const submitBtn = document.getElementById('ga-action-submit');
    const subtitle = document.getElementById('ga-action-subtitle');
    const title = document.getElementById('ga-reject-title');
    const form = document.getElementById('ga-action-form');

    if (!overlay || !reason || !cancelBtn || !submitBtn || !form) return;

    let currentReportNo = '';
    let currentSubject = '';
    let currentAction = '';

    function openModal(action, reportNo, subject) {
        currentAction = action;
        currentReportNo = reportNo;
        currentSubject = subject;

        if (action === 'return') {
            title.textContent = 'Return Report';
            subtitle.textContent = 'Explain why this needs revision by GA Staff.';
            submitBtn.textContent = 'Confirm Return';
            submitBtn.className = 'btn btn-warning btn-sm text-white';
        } else {
            title.textContent = 'Reject Report';
            subtitle.textContent = 'Explain why this report is being rejected.';
            submitBtn.textContent = 'Confirm Reject';
            submitBtn.className = 'btn btn-destructive btn-sm';
        }

        meta.textContent = `Report: ${reportNo} - ${subject}`;
        reason.value = '';
        overlay.classList.remove('hidden');
        overlay.classList.add('active');
        setTimeout(() => reason.focus(), 50);
    }

    function closeModal() {
        overlay.classList.remove('active');
        overlay.classList.add('hidden');
    }

    document.querySelectorAll('[data-reject-report-no]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('reject', btn.getAttribute('data-reject-report-no'), btn.getAttribute('data-reject-subject'));
        });
    });

    document.querySelectorAll('[data-return-report-no]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('return', btn.getAttribute('data-return-report-no'), btn.getAttribute('data-return-subject'));
        });
    });

    cancelBtn.addEventListener('click', closeModal);

    submitBtn.addEventListener('click', () => {
        const val = reason.value.trim();
        if (!val) return alert('Reason is required');
        form.querySelector('[name="action"]').value = currentAction;
        form.querySelector('[name="report_no"]').value = currentReportNo;
        form.querySelector('[name="notes"]').value = val;
        form.submit();
    });
})();
</script>

<script>
    (function () {
        const buildingEl = document.getElementById('building-filter');
        const severityEl = document.getElementById('severity-filter');
        const deptEl = document.getElementById('department-filter');
        const searchEl = document.getElementById('search-filter');
        const clearBtn = document.getElementById('clear-filters');
        const summaryEl = document.getElementById('ga-filter-summary');
        const emptyEl = document.getElementById('ga-empty-filter');
        const cards = Array.from(document.querySelectorAll('.ga-pending-card'));

        if (!cards.length || !severityEl || !deptEl || !searchEl || !clearBtn || !summaryEl || !emptyEl) return;

        function applyFilters() {
            const sev = severityEl.value;
            const entity = (buildingEl && buildingEl.value) ? buildingEl.value.toLowerCase() : 'all';
            const dept = deptEl.value;
            const term = searchEl.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach((card) => {
                const cardSev = card.getAttribute('data-severity') || '';
                const cardEntity = card.getAttribute('data-entity') || '';
                const cardDept = card.getAttribute('data-department') || '';
                const cardSearch = card.getAttribute('data-search') || '';

                const matchSev = sev === 'all' || cardSev === sev;
                const matchEntity = entity === 'all' || cardEntity === entity;
                const matchDept = dept === 'all' || cardDept === dept;
                const matchTerm = term === '' || cardSearch.includes(term);

                const show = matchSev && matchEntity && matchDept && matchTerm;
                const cardCol = card.closest('.ga-pending-col');
                if (cardCol) {
                    cardCol.style.display = show ? '' : 'none';
                } else {
                    card.style.display = show ? '' : 'none';
                }
                if (show) visible += 1;
            });

            emptyEl.classList.toggle('hidden', visible !== 0);
            summaryEl.textContent = `Showing ${visible} pending report${visible === 1 ? '' : 's'}`;
        }

        if (buildingEl) {
            buildingEl.addEventListener('change', applyFilters);
        }
        severityEl.addEventListener('change', applyFilters);
        deptEl.addEventListener('change', applyFilters);
        searchEl.addEventListener('input', applyFilters);

        clearBtn.addEventListener('click', () => {
            if (buildingEl) buildingEl.value = 'all';
            severityEl.value = 'all';
            deptEl.value = 'all';
            searchEl.value = '';
            applyFilters();
        });

        applyFilters();
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
