<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-arrow-counterclockwise me-2 text-primary"></i>Returned Reports</h1>
                <p class="text-sm text-muted-foreground mb-0">
                    Reports returned by GA Manager for editing and resubmission.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 align-self-end">
                <span class="text-xs text-muted-foreground">Building</span>
                <select id="building-filter" class="form-select form-select-sm" style="min-width: 160px;">
                    <option value="all" <?php echo $selectedBuilding === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
                    <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
                </select>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="table-container table-card" style="--table-accent: var(--destructive)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Returned Reports</h3>
                    <p class="text-xs text-muted-foreground">Click a row to preview. Edit/resubmit when ready.</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int) count($returned); ?></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Subject</th>
                        <th>Department</th>
                        <th>Severity</th>
                        <th>Date Submitted</th>
                        <th>President Feedback</th>
                        <th>Return Reason</th>
                        <th style="width: 160px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returned)): ?>
                        <tr><td colspan="8" class="text-center text-muted-foreground">No returned reports.</td></tr>
                    <?php else: ?>
                        <?php foreach ($returned as $r): ?>
                            <?php
                            $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                            $sevBadge = 'badge--muted';
                            if ($sevRaw === 'critical') {
                                $sevBadge = 'badge--destructive';
                            } elseif ($sevRaw === 'high') {
                                $sevBadge = 'badge--warning';
                            } elseif ($sevRaw === 'medium') {
                                $sevBadge = 'badge--info';
                            } elseif ($sevRaw === 'low') {
                                $sevBadge = 'badge--muted';
                            }
                            ?>
                            <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
                                $r['report_no'],
                            ); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars(
                                    $r['report_no'],
                                ); ?></td>
                                <td class="text-truncate fw-medium" style="max-width: 240px;"><?php echo htmlspecialchars(
                                    $r['subject'],
                                ); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                    $r['department_name'],
                                ); ?></td>
                                <td><span class="badge <?php echo htmlspecialchars(
                                    $sevBadge,
                                ); ?>"><?php echo htmlspecialchars(
    severity_label((string) $r['severity']),
); ?></span></td>
                                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                    date('M d, Y', strtotime($r['submitted_at'])),
                                ); ?></td>
                                <td class="text-muted-foreground text-truncate" style="max-width: 220px;"><?php echo htmlspecialchars(
                                    $r['president_notes'] ?? '—',
                                ); ?></td>
                                <td class="text-muted-foreground text-truncate" style="max-width: 220px;"><?php echo htmlspecialchars(
                                    $r['return_reason'] ?? '—',
                                ); ?></td>
                                <td onclick="event.stopPropagation();">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="GaStaffReturn.open('<?php echo htmlspecialchars(
                                        $r['report_no'],
                                    ); ?>')">Edit / Resubmit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Returned Edit Modal -->
<div id="ga-return-modal-overlay" class="modal-overlay">
    <div class="report-modal">
        <div class="report-modal-header">
            <h3 id="ga-return-modal-title">Edit Returned Report</h3>
            <button class="modal-close-btn" onclick="GaStaffReturn.close()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="report-modal-body">
            <form method="POST" id="ga-return-form" class="d-grid gap-2" onsubmit="return GaStaffReturn.beforeSubmit(event)">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                <input type="hidden" name="action" value="resubmit" />
                <input type="hidden" name="report_no" id="ga-return-report-no" value="" />

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="text-xs text-muted-foreground">Subject *</label>
                        <input type="text" name="subject" id="ga-return-subject" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;" />
                    </div>
                    <div>
                        <label class="text-xs text-muted-foreground">Category *</label>
                        <input type="text" name="category" id="ga-return-category" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;" />
                    </div>
                </div>

                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="text-xs text-muted-foreground">Location *</label>
                        <input type="text" name="location" id="ga-return-location" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;" />
                    </div>
                    <div>
                        <label class="text-xs text-muted-foreground">Severity *</label>
                        <select name="severity" id="ga-return-severity" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-muted-foreground">Department *</label>
                    <select name="responsible_department_id" id="ga-return-dept" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px;">
                        <option value="">Select department</option>
                        <?php foreach ($departmentsDb ?? [] as $d): ?>
                            <option value="<?php echo (int) $d['id']; ?>"><?php echo htmlspecialchars(
    $d['name'],
); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-muted-foreground">Details *</label>
                    <textarea name="details" id="ga-return-details" rows="4" required style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; resize: vertical;"></textarea>
                </div>

                <div>
                    <label class="text-xs text-muted-foreground">Security Actions Taken</label>
                    <textarea name="actions_taken" id="ga-return-actions" rows="3" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; resize: vertical;"></textarea>
                </div>

                <div>
                    <label class="text-xs text-muted-foreground">Remarks</label>
                    <textarea name="remarks" id="ga-return-remarks" rows="3" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; resize: vertical;"></textarea>
                </div>

                <div>
                    <label class="text-xs text-muted-foreground">GA Manager Feedback</label>
                    <div id="ga-return-manager-notes" class="text-sm" style="white-space: pre-wrap; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; background: hsl(var(--muted));">—</div>
                </div>

                <div id="ga-return-evidence" class="hidden"></div>

                <div>
                    <label class="text-xs text-muted-foreground">GA Staff Notes (optional)</label>
                    <textarea name="notes" id="ga-return-notes" rows="2" style="width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: 6px; resize: vertical;"></textarea>
                </div>

                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 8px;">
                    <button type="submit" class="btn btn-primary btn-sm">Resubmit to GA Manager</button>
                    <button type="button" class="modal-close-action" onclick="GaStaffReturn.close()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const GaStaffReturn = {
  overlay: null,
  init() {
    this.overlay = document.getElementById('ga-return-modal-overlay');
    if (this.overlay) {
      this.overlay.addEventListener('click', (e) => {
        if (e.target === this.overlay) this.close();
      });
    }
  },
  esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  },
  async open(reportNo) {
    if (!this.overlay) this.init();

    document.getElementById('ga-return-modal-title').textContent = `Edit ${reportNo}`;
    document.getElementById('ga-return-report-no').value = reportNo;
    document.getElementById('ga-return-notes').value = '';
    document.getElementById('ga-return-manager-notes').textContent = 'Loading…';

    this.overlay.classList.add('active');

    try {
      const res = await fetch(`api/report.php?id=${encodeURIComponent(reportNo)}`, { credentials: 'same-origin' });
      const data = await res.json();
      if (!res.ok) throw new Error(data && data.error ? data.error : 'Failed to load report');

      document.getElementById('ga-return-subject').value = data.subject || '';
      document.getElementById('ga-return-category').value = data.category || '';
      document.getElementById('ga-return-location').value = data.location || '';
      document.getElementById('ga-return-severity').value = data.severity || 'low';
      document.getElementById('ga-return-details').value = data.details || '';
      document.getElementById('ga-return-actions').value = data.actionsTaken || '';
      document.getElementById('ga-return-remarks').value = data.remarks || '';

      if (data.departmentId) {
        document.getElementById('ga-return-dept').value = String(data.departmentId);
      }

    document.getElementById('ga-return-manager-notes').textContent = data.gaManagerNotes || '—';

      const ev = document.getElementById('ga-return-evidence');
      if (data.evidenceImageUrl) {
        ev.classList.remove('hidden');
        ev.innerHTML = `
          <div class="mt-3">
            <div class="text-xs text-muted-foreground mb-1">Evidence Image</div>
            <img src="${this.esc(data.evidenceImageUrl)}" alt="Evidence" style="max-width: 100%; border-radius: 8px; border: 1px solid hsl(var(--border));" />
          </div>
        `;
      } else {
        ev.classList.add('hidden');
        ev.innerHTML = '';
      }

    } catch (err) {
    document.getElementById('ga-return-manager-notes').textContent = String(err.message || err);
    }
  },
  close() {
    if (!this.overlay) return;
        this.overlay.classList.remove('active');
  },
  beforeSubmit() {
    return confirm('Resubmit this report to GA Manager?');
  }
};

document.addEventListener('DOMContentLoaded', () => GaStaffReturn.init());
</script>

<script>
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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
