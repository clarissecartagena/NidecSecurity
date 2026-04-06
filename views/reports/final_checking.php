<style>
/* ── Final Checking Card Design ── */
.fc-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    transition: box-shadow 0.18s ease, border-color 0.18s ease;
    position: relative;
}
.fc-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: hsl(var(--destructive));
    border-radius: var(--radius) 0 0 var(--radius);
}
.fc-card:hover {
    box-shadow: 0 4px 20px hsl(var(--destructive) / 0.12);
    border-color: hsl(var(--destructive) / 0.35);
}
.fc-card-body {
    padding: 1.1rem 1.25rem 1.1rem 1.5rem;
}
.fc-meta-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.6rem 1rem;
    margin-top: 0.75rem;
    margin-bottom: 0.75rem;
}
.fc-meta-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    background: hsl(var(--muted) / 0.45);
    border-radius: 6px;
    padding: 0.45rem 0.6rem;
}
.fc-meta-label {
    font-size: 0.68rem;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.fc-meta-label i { font-size: 0.7rem; opacity: 0.75; }
.fc-meta-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.fc-details-snippet {
    font-size: 0.82rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.55;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.fc-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem 0.85rem 1.5rem;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.3);
    flex-wrap: wrap;
}
.fc-age-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    font-weight: 500;
    padding: 0.2rem 0.55rem;
    border-radius: 99px;
    background: hsl(var(--warning) / 0.14);
    color: hsl(var(--warning));
    border: 1px solid hsl(var(--warning) / 0.3);
    white-space: nowrap;
}
.fc-age-badge.overdue {
    background: hsl(var(--destructive) / 0.12);
    color: hsl(var(--destructive));
    border-color: hsl(var(--destructive) / 0.3);
}

</style>

<main class="main-content">
    <div class="animate-fade-in">

        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-patch-check-fill me-2 text-primary"></i>Security Final Checking<?php if (
                !empty($reports)
            ): ?> <span class="badge badge--destructive" style="font-size:0.72rem; padding:0.25rem 0.6rem; vertical-align:middle; margin-left:0.4rem;"><?= count(
     $reports,
 ) ?> Pending</span><?php endif; ?></h1>
            <p class="text-sm text-muted-foreground mb-0">Perform re-inspection and confirm resolution of reports</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> mb-4" role="alert">
                <?= htmlspecialchars($flash) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($reports)): ?>
            <div class="table-container d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height:260px;">
                <i class="bi bi-check-circle-fill mb-3" style="font-size:3rem; color:hsl(var(--success));"></i>
                <p class="fw-semibold text-foreground mb-1">All Clear</p>
                <p class="text-sm text-muted-foreground">No reports are currently awaiting your final check.</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
            <?php foreach ($reports as $r):

                $days = (int) ($r['days_pending'] ?? 0);
                $sevBadge = match (strtolower($r['severity'] ?? '')) {
                    'critical' => 'badge--destructive',
                    'high' => 'badge--warning',
                    'medium' => 'badge--info',
                    'low' => 'badge--success',
                    default => 'badge--muted',
                };
                $ageClass = $days >= 7 ? 'overdue' : '';
                $ageLabel = $days === 0 ? 'Today' : ($days === 1 ? '1 day ago' : "{$days} days ago");
                ?>
            <div class="fc-card">
                <div class="fc-card-body">
                    <!-- Top row: report id, badges -->
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <span class="font-mono text-xs text-muted-foreground fw-medium"><?= htmlspecialchars(
                            $r['report_no'],
                        ) ?></span>
                        <span class="badge <?= $sevBadge ?>"><?= htmlspecialchars(
    severity_label($r['severity']),
) ?></span>
                        <span class="badge badge--muted">For Final Check</span>
                    </div>

                    <!-- Subject -->
                    <h5 class="fw-bold text-foreground mb-0" style="font-size:1.05rem; line-height:1.3;">
                        <?= htmlspecialchars($r['subject']) ?>
                    </h5>

                    <!-- Meta grid -->
                    <div class="fc-meta-row">
                        <?php if (!empty($r['category'])): ?>
                        <div class="fc-meta-item">
                            <span class="fc-meta-label"><i class="bi bi-tag"></i> Category</span>
                            <span class="fc-meta-value"><?= htmlspecialchars($r['category']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($r['location'])): ?>
                        <div class="fc-meta-item">
                            <span class="fc-meta-label"><i class="bi bi-geo-alt"></i> Location</span>
                            <span class="fc-meta-value"><?= htmlspecialchars($r['location']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="fc-meta-item">
                            <span class="fc-meta-label"><i class="bi bi-building"></i> Department</span>
                            <span class="fc-meta-value"><?= htmlspecialchars($r['department_name']) ?></span>
                        </div>
                        <div class="fc-meta-item">
                            <span class="fc-meta-label"><i class="bi bi-calendar3"></i> Submitted</span>
                            <span class="fc-meta-value"><?= htmlspecialchars(
                                date('M d, Y', strtotime($r['submitted_at'])),
                            ) ?></span>
                        </div>
                    </div>

                    <?php if (!empty($r['details'])): ?>
                    <hr class="my-2" style="border-color:hsl(var(--border));">
                    <p class="fc-details-snippet mb-0"><?= htmlspecialchars($r['details']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="fc-card-footer">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fc-age-badge <?= $ageClass ?>">
                            <i class="bi bi-clock"></i> <?= $ageLabel ?>
                        </span>
                        <button type="button" class="btn btn-ghost btn-sm text-xs"
                            onclick="ReportModal.open('<?= htmlspecialchars($r['report_no']) ?>')"
                            style="padding:0.2rem 0.6rem; font-size:0.78rem;">
                            <i class="bi bi-eye me-1"></i>View Full Report
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button"
                            class="btn btn-destructive btn-sm d-inline-flex align-items-center gap-1"
                            onclick="FCRemarks.open('<?= htmlspecialchars($r['report_no']) ?>', 'not_resolved')">
                            <i class="bi bi-arrow-return-left"></i> Not Resolved
                        </button>
                        <button type="button"
                            class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1"
                            onclick="FCRemarks.open('<?= htmlspecialchars($r['report_no']) ?>', 'confirm_resolved')">
                            <i class="bi bi-check-lg"></i> Confirm Resolved
                        </button>
                    </div>
                </div>
            </div>
            <?php
            endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- ── Remarks Modal ── -->
<div id="fc-modal-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="fc-modal-title">
    <div class="report-modal report-modal--sm">
        <div class="report-modal-header">
            <div>
                <h3 id="fc-modal-title">Add Remarks</h3>
                <p class="modal-header-subtitle" id="fc-modal-subtitle"></p>
            </div>
            <button type="button" class="modal-close-btn" onclick="FCRemarks.close()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="report-modal-body" style="padding: 1.25rem;">
            <label for="fc-remarks-input" class="form-label fw-medium">
                Remarks <span class="text-danger">*</span>
            </label>
            <textarea id="fc-remarks-input" class="form-control" rows="4"
                placeholder="Describe the outcome of your re-inspection..."></textarea>
            <p class="text-xs text-muted-foreground mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>Required — will be saved in report history.
            </p>
        </div>
        <div class="report-modal-footer">
            <button type="button" class="btn btn-ghost btn-sm" onclick="FCRemarks.close()">Cancel</button>
            <button type="button" id="fc-modal-confirm-btn" class="btn btn-sm d-inline-flex align-items-center gap-1" onclick="FCRemarks.submit()">
                <!-- label set dynamically -->
            </button>
        </div>
    </div>
</div>

<!-- Hidden submission forms -->
<form method="POST" id="fc-form-not-resolved" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <input type="hidden" name="action" value="not_resolved" />
    <input type="hidden" name="report_no" id="fc-rno-not-resolved" value="" />
    <input type="hidden" name="final_remarks" id="fc-rmk-not-resolved" value="" />
</form>
<form method="POST" id="fc-form-confirm-resolved" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
    <input type="hidden" name="action" value="confirm_resolved" />
    <input type="hidden" name="report_no" id="fc-rno-confirm-resolved" value="" />
    <input type="hidden" name="final_remarks" id="fc-rmk-confirm-resolved" value="" />
</form>

<script>
const FCRemarks = {
    overlay: null,
    currentReportNo: null,
    currentAction: null,

    init() {
        this.overlay = document.getElementById('fc-modal-overlay');
        if (!this.overlay) return;
        this.overlay.addEventListener('click', e => { if (e.target === this.overlay) this.close(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && this.isOpen()) this.close(); });
    },

    isOpen() { return this.overlay && this.overlay.classList.contains('active'); },

    open(reportNo, action) {
        this.currentReportNo = reportNo;
        this.currentAction   = action;

        const subtitle = document.getElementById('fc-modal-subtitle');
        const btn      = document.getElementById('fc-modal-confirm-btn');
        const input    = document.getElementById('fc-remarks-input');

        input.value = '';

        if (action === 'confirm_resolved') {
            subtitle.textContent = reportNo + ' — confirming issue is resolved';
            btn.className = 'btn btn-primary btn-sm d-inline-flex align-items-center gap-1';
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirm Resolved';
        } else {
            subtitle.textContent = reportNo + ' — marking as not yet resolved';
            btn.className = 'btn btn-destructive btn-sm d-inline-flex align-items-center gap-1';
            btn.innerHTML = '<i class="bi bi-arrow-return-left"></i> Not Resolved';
        }

        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => input.focus(), 80);
    },

    close() {
        if (this.overlay) { this.overlay.classList.remove('active'); document.body.style.overflow = ''; }
    },

    submit() {
        const remarks = (document.getElementById('fc-remarks-input').value || '').trim();
        if (!remarks) {
            document.getElementById('fc-remarks-input').classList.add('is-invalid');
            document.getElementById('fc-remarks-input').focus();
            return;
        }
        document.getElementById('fc-remarks-input').classList.remove('is-invalid');

        const action = this.currentAction;
        const suffix = action === 'confirm_resolved' ? 'confirm-resolved' : 'not-resolved';

        document.getElementById('fc-rno-'  + suffix).value = this.currentReportNo;
        document.getElementById('fc-rmk-' + suffix).value = remarks;
        document.getElementById('fc-form-' + suffix).submit();
    }
};

document.addEventListener('DOMContentLoaded', () => FCRemarks.init());
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
