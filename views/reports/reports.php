<?php
// --- KEEP THESE HELPER FUNCTIONS ---
function getStatusBadge($status)
{
    $status = (string) ($status ?? '');
    $variant = 'badge--muted';
    if ($status === 'submitted_to_ga_manager') {
        $variant = 'badge--warning';
    } elseif (in_array($status, ['sent_to_department', 'under_department_fix'], true)) {
        $variant = 'badge--info';
    } elseif ($status === 'for_security_final_check') {
        $variant = 'badge--primary';
    } elseif ($status === 'returned_to_department') {
        $variant = 'badge--destructive';
    } elseif ($status === 'resolved') {
        $variant = 'badge--success';
    }
    return '<span class="badge ' . $variant . '">' . htmlspecialchars(report_status_label($status)) . '</span>';
}

function getSeverityBadge($severity)
{
    $sev = strtolower((string) ($severity ?? ''));
    $variant = 'badge--muted';
    if ($sev === 'critical') {
        $variant = 'badge--destructive';
    } elseif ($sev === 'high') {
        $variant = 'badge--warning';
    } elseif ($sev === 'medium') {
        $variant = 'badge--info';
    } elseif ($sev === 'low') {
        $variant = 'badge--muted';
    }
    return '<span class="badge ' . $variant . '">' . htmlspecialchars(severity_label($sev)) . '</span>';
}

function formatDate($dateString)
{
    return date('M d, Y', strtotime($dateString));
}
?>

<style>
    /* Force the table layout */
    #reports-table {
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse;
    }

    /* Target BOTH headers (th) and data (td) */
    #reports-table th,
    #reports-table td {
        text-align: center;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding: 12px 8px;
    }

    /* NEW: This forces the Subject column data to align left */
    #reports-table td.subject-column {
        text-align: left !important;
        padding-left: 15px !important;
    }

    /* Optional: Header styling to make titles stand out */
    #reports-table th {
        background-color: var(--muted);
        font-weight: 600;
    }

    /* Column widths */
    .col-id { width: 12%; }
    .col-subject { width: 30%; }
    .col-cat { width: 12%; }
    .col-sev { width: 10%; }
    .col-dept { width: 14%; }
    .col-status { width: 12%; }
    .col-date { width: 10%; }

    .reports-filter-wrap {
        background: hsl(var(--card));
        border: 1px solid hsl(var(--border));
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }

    .reports-filter-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: hsl(var(--muted-foreground));
        font-weight: 700;
    }

    .reports-filter-wrap .form-select,
    .reports-filter-wrap .form-control {
        height: 38px;
        min-height: 38px;
        font-size: 14px;
    }

    #reports-clear-filters {
        height: 38px;
        min-height: 38px;
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
        font-weight: 600;
    }

    #reports-clear-filters:hover,
    #reports-clear-filters:focus {
        background-color: #bb2d3b;
        border-color: #b02a37;
        color: #fff;
    }
</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>All Reports</h1>
            <p class="text-sm text-muted-foreground mb-0">View and manage all security reports</p>
        </div>

        <div class="reports-filter-wrap">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="reports-filter-label mb-1" for="search-input">Search</label>
                    <input type="text" id="search-input" placeholder="Search report no, subject, category, location..." class="form-control" />
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="reports-filter-label mb-1" for="category-filter">Category</label>
                    <select id="category-filter" class="form-select">
                        <option value="all">All</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-1">
                    <label class="reports-filter-label mb-1" for="building-filter">Entity</label>
                    <select id="building-filter" class="form-select">
                        <option value="all" <?php echo $selectedBuilding === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
                        <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-1">
                    <label class="reports-filter-label mb-1" for="severity-filter">Severity</label>
                    <select id="severity-filter" class="form-select">
                        <option value="all">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="reports-filter-label mb-1" for="department-filter">Department</label>
                    <select id="department-filter" class="form-select">
                        <option value="all">All</option>
                        <?php foreach ($departmentsDb ?? [] as $d): ?>
                            <?php $depName = strtolower(trim((string) ($d['name'] ?? ''))); ?>
                            <?php if ($depName === '') {
                                continue;
                            } ?>
                            <option value="<?php echo htmlspecialchars($depName); ?>"><?php echo htmlspecialchars(
    (string) $d['name'],
); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-2">
                    <label class="reports-filter-label mb-1" for="status-filter">Status</label>
                    <select id="status-filter" class="form-select">
                        <option value="all">All</option>
                        <option value="submitted_to_ga_manager">Waiting GA Manager</option>
                        <option value="sent_to_department">Sent to Department</option>
                        <option value="under_department_fix">Under Department Fix</option>
                        <option value="for_security_final_check">Security Final Check</option>
                        <option value="returned_to_department">Returned to Department</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-1">
                    <button id="reports-clear-filters" type="button" class="btn w-100">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container table-card" style="--table-accent: var(--primary)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Reports</h3>
                    <p class="text-xs text-muted-foreground">Click a row to view details</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int) count($reports); ?></div>
            </div>
            <div class="table-responsive">
            <table id="reports-table" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 10%;">Report ID</th>
                        <th style="width: 20%;">Subject</th>
                        <th style="width: 13%;">Category</th>
                        <th style="width: 8%;">Severity</th>
                        <th style="width: 15%;">Department</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="7" class="text-center text-muted-foreground">No reports found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <?php
                            $entityName = trim((string) ($report['building'] ?? ''));
                            if ($entityName === '') {
                                $subjectUpper = strtoupper((string) ($report['subject'] ?? ''));
                                if (str_contains($subjectUpper, 'NCFL')) {
                                    $entityName = 'NCFL';
                                } elseif (str_contains($subjectUpper, 'NPFL')) {
                                    $entityName = 'NPFL';
                                }
                            }
                            $searchBlob = strtolower(
                                trim(
                                    (string) ($report['report_no'] ?? '') .
                                        ' ' .
                                        (string) ($report['subject'] ?? '') .
                                        ' ' .
                                        (string) ($report['category'] ?? '') .
                                        ' ' .
                                        (string) ($report['department_name'] ?? '') .
                                        ' ' .
                                        (string) ($report['severity'] ?? '') .
                                        ' ' .
                                        (string) ($report['status'] ?? ''),
                                ),
                            );
                            ?>
                            <tr
                                data-status="<?php echo htmlspecialchars($report['status']); ?>"
                                data-category="<?php echo htmlspecialchars(
                                    strtolower((string) ($report['category'] ?? '')),
                                ); ?>"
                                data-entity="<?php echo htmlspecialchars(strtolower($entityName)); ?>"
                                data-severity="<?php echo htmlspecialchars(
                                    strtolower((string) ($report['severity'] ?? '')),
                                ); ?>"
                                data-department="<?php echo htmlspecialchars(
                                    strtolower((string) ($report['department_name'] ?? '')),
                                ); ?>"
                                data-search="<?php echo htmlspecialchars($searchBlob); ?>"
                                class="clickable-row"
                                onclick="ReportModal.open('<?php echo htmlspecialchars($report['report_no']); ?>')"
                            >
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars(
                                    $report['report_no'],
                                ); ?></td>
                                <td class="font-medium subject-column text-truncate" style="max-width: 360px;">
                                    <?php echo htmlspecialchars($report['subject']); ?>
                                </td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                    $report['category'],
                                ); ?></td>
                                <td><?php echo getSeverityBadge($report['severity']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                    $report['department_name'],
                                ); ?></td>
                                <td><?php echo getStatusBadge($report['status']); ?></td>
                                <td class="text-muted-foreground text-xs"><?php echo formatDate(
                                    $report['submitted_at'],
                                ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <div class="p-3 border-t d-flex align-items-center justify-content-between gap-2 flex-wrap bg-body-tertiary">
                <div class="text-xs text-muted-foreground">
                    Showing <?php echo $offset + 1; ?> to <?php echo min(
     $offset + $limit,
     $totalReports,
 ); ?> of <?php echo $totalReports; ?> reports
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(
                            array_merge($_GET, ['p' => $page - 1]),
                        ); ?>" class="btn btn-outline-secondary btn-sm">Previous</a>
                    <?php endif; ?>

                    <?php // Simple page number display

for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 || $i == $totalPages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>"
                               class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif;
                    endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(
                            array_merge($_GET, ['p' => $page + 1]),
                        ); ?>" class="btn btn-outline-secondary btn-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (() => {
        const table = document.getElementById('reports-table');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const dataRows = Array.from(tbody.querySelectorAll('tr.clickable-row'));
        if (!dataRows.length) return;

        const searchEl = document.getElementById('search-input');
        const categoryEl = document.getElementById('category-filter');
        const entityEl = document.getElementById('building-filter');
        const severityEl = document.getElementById('severity-filter');
        const departmentEl = document.getElementById('department-filter');
        const statusEl = document.getElementById('status-filter');
        const clearEl = document.getElementById('reports-clear-filters');

        if (!searchEl || !categoryEl || !entityEl || !severityEl || !departmentEl || !statusEl || !clearEl) return;

        const categoryValues = Array.from(new Set(dataRows.map((row) => (row.getAttribute('data-category') || '').trim()).filter(Boolean))).sort();
        const departmentValues = Array.from(new Set(dataRows.map((row) => (row.getAttribute('data-department') || '').trim()).filter(Boolean))).sort();

        categoryValues.forEach((value) => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value.replace(/\b\w/g, (m) => m.toUpperCase());
            categoryEl.appendChild(opt);
        });

        const existingDepartmentValues = new Set(Array.from(departmentEl.options).map((opt) => String(opt.value || '').trim()).filter(Boolean));
        departmentValues.forEach((value) => {
            if (!value || existingDepartmentValues.has(value)) return;
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value.replace(/\b\w/g, (m) => m.toUpperCase());
            departmentEl.appendChild(opt);
            existingDepartmentValues.add(value);
        });

        function applyFilters() {
            const searchVal = searchEl.value.trim().toLowerCase();
            const categoryVal = categoryEl.value;
            const entityVal = entityEl.value.toLowerCase();
            const severityVal = severityEl.value;
            const departmentVal = departmentEl.value;
            const statusVal = statusEl.value;

            let visibleCount = 0;

            dataRows.forEach((row) => {
                const category = row.getAttribute('data-category') || '';
                const entity = row.getAttribute('data-entity') || '';
                const severity = row.getAttribute('data-severity') || '';
                const department = row.getAttribute('data-department') || '';
                const status = row.getAttribute('data-status') || '';
                const searchBlob = row.getAttribute('data-search') || '';

                const matchSearch = searchVal === '' || searchBlob.includes(searchVal);
                const matchCategory = categoryVal === 'all' || category === categoryVal;
                const matchEntity = entityVal === 'all' || entity === entityVal;
                const matchSeverity = severityVal === 'all' || severity === severityVal;
                const matchDepartment = departmentVal === 'all' || department === departmentVal;
                const matchStatus = statusVal === 'all' || status === statusVal;

                const show = matchSearch && matchCategory && matchEntity && matchSeverity && matchDepartment && matchStatus;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount += 1;
            });

            const totalInfo = document.querySelector('.table-container .p-3.border-b .text-xs.text-muted-foreground:last-child');
            if (totalInfo) {
                totalInfo.textContent = `Visible: ${visibleCount} / Total: ${dataRows.length}`;
            }
        }

        [searchEl, categoryEl, entityEl, severityEl, departmentEl, statusEl].forEach((el) => {
            const eventName = el.tagName === 'INPUT' ? 'input' : 'change';
            el.addEventListener(eventName, applyFilters);
        });

        clearEl.addEventListener('click', () => {
            searchEl.value = '';
            categoryEl.value = 'all';
            entityEl.value = 'all';
            severityEl.value = 'all';
            departmentEl.value = 'all';
            statusEl.value = 'all';
            applyFilters();
        });

        applyFilters();
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
