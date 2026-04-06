<main class="main-content">
  <div class="animate-fade-in">
    <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-clipboard2-check me-2 text-primary"></i>Assigned Reports</h1>
        <p class="text-sm text-muted-foreground mb-0">Reports assigned to your department for action</p>
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

    <div class="table-container table-card" style="--table-accent: var(--primary);">
      <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <h3 class="font-semibold text-foreground">Assigned Reports</h3>
          <p class="text-xs text-muted-foreground">Click a row to view details. Use “Set Timeframe” to commit a due date.</p>
        </div>
        <div class="text-xs text-muted-foreground">Total: <?php echo (int) count($assigned); ?></div>
      </div>
      <div class="table-responsive">
        <table id="dept-assigned-table" class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Report ID</th>
            <th>Subject</th>
            <th>Severity</th>
            <th>Location</th>
            <th>Date Received</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($assigned)): ?>
            <tr><td colspan="7" class="text-center text-muted-foreground">No assigned reports.</td></tr>
          <?php else: ?>
            <?php foreach ($assigned as $r): ?>
              <tr class="clickable-row" onclick="DepartmentActionModal.open('<?php echo htmlspecialchars(
                  $r['report_no'],
              ); ?>')">
                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($r['report_no']); ?></td>
                <td class="text-truncate fw-medium" style="max-width: 260px;"><?php echo htmlspecialchars(
                    $r['subject'],
                ); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars(severity_label($r['severity'])); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['location']); ?></td>
                <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                    date('M d, Y', strtotime($r['assigned_at'])),
                ); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                    report_status_label($r['status']),
                ); ?></td>
                <td>
                  <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    onclick="event.stopPropagation(); DepartmentActionModal.open('<?php echo htmlspecialchars(
                        $r['report_no'],
                    ); ?>', true)"
                  >Set Timeframe</button>
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


<style>
/* Mark-as-Done Confirmation Modal - Refined */
#done-confirm-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(8px);
    z-index: 2200; /* Above everything */
    align-items: center;
    justify-content: center;
    padding: 20px;
}

#done-confirm-overlay.active { display: flex; }

.done-confirm-content {
    background: #ffffff;
    width: 100%;
    max-width: 440px;
    padding: 40px;
    border-radius: 32px;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: none;
    text-align: left;
}

.done-confirm-close-btn {
    position: absolute;
    top: 24px;
    right: 24px;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.done-confirm-close-btn:hover { background: #e2e8f0; color: #0f172a; }

.done-confirm-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0fdf4;
    color: #16a34a;
    padding: 8px 16px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.5px;
    margin-bottom: 24px;
}

.done-confirm-icon-circle {
    width: 64px;
    height: 64px;
    background: #f0fdf4;
    color: #22c55e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin-bottom: 24px;
}

.done-confirm-title {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}

.done-confirm-message {
    color: #64748b;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 32px;
}

.done-confirm-footer {
    display: flex;
    gap: 16px;
}

.btn-done-cancel, .btn-done-confirm {
    flex: 1;
    border: none !important;
    padding: 16px 24px;
    border-radius: 16px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: transform 0.1s, opacity 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-done-cancel { background: #f1f5f9; color: #475569; }
.btn-done-confirm { background: #16a34a; color: white; }

.btn-done-cancel:hover { background: #e2e8f0; }
.btn-done-confirm:hover { background: #15803d; }
.btn-done-confirm:active { transform: scale(0.98); }
</style>
<!-- Mark as Done Confirmation Modal -->
<div id="done-confirm-overlay" class="modal-overlay">
    <div class="done-confirm-content">
        <button type="button" class="done-confirm-close-btn" onclick="MarkDoneConfirm.close()">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <div class="done-confirm-header">
            <div class="done-confirm-chip">
                <i class="bi bi-check2-all"></i>
                MARK AS DONE
            </div>
            
            <div class="done-confirm-icon-circle">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            
            <h3 class="done-confirm-title">Mark Report as Done?</h3>
            <p class="done-confirm-message">This will send the report to Security for final checking. This action cannot be undone.</p>
        </div>

        <div class="done-confirm-footer">
            <button type="button" class="btn-done-cancel" onclick="MarkDoneConfirm.close()">Cancel</button>
            <button type="button" class="btn-done-confirm" onclick="MarkDoneConfirm.confirm()">
                <i class="bi bi-check-circle-fill me-2"></i> Yes, Mark as Done
            </button>
        </div>
    </div>
</div>


<!-- Department Action Modal -->
<div id="dept-action-overlay" class="modal-overlay">
  <div id="dept-action-modal" class="report-modal">
    <div class="report-modal-header">
      <h3 id="dept-action-subject">Department Action</h3>
      <button class="modal-close-btn" onclick="DepartmentActionModal.close()">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>
    </div>

    <div class="report-modal-body" id="dept-action-content">
      <div class="text-sm text-muted-foreground">Loading report details...</div>
    </div>

    <div class="report-modal-footer" style="display:flex; gap: 8px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
      <form method="POST" id="dept-timeline-form" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
        <input type="hidden" name="action" value="set_timeline" />
        <input type="hidden" name="report_no" id="dept-report-no-1" value="" />
        <input type="hidden" name="timeline_days" id="dept-timeline-days" value="" />
        <button type="submit" class="btn btn-primary" onclick="return DepartmentActionModal.submitTimeline();">Set Timeframe</button>
      </form>

      <form method="POST" id="dept-done-form" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
        <input type="hidden" name="action" value="mark_done" />
        <input type="hidden" name="report_no" id="dept-report-no-2" value="" />
        <button type="button" class="btn btn-outline" onclick="MarkDoneConfirm.open()">Mark Done</button>
      </form>
    </div>
  </div>
</div>

<script>
const DepartmentActionModal = {
  overlay: null,
  subjectEl: null,
  contentEl: null,
  reportNo: null,

  init() {
    this.overlay = document.getElementById('dept-action-overlay');
    this.subjectEl = document.getElementById('dept-action-subject');
    this.contentEl = document.getElementById('dept-action-content');
    if (!this.overlay) return;

    this.overlay.addEventListener('click', (e) => {
      if (e.target === this.overlay) this.close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isOpen()) this.close();
    });
  },

  isOpen() {
    return this.overlay && this.overlay.classList.contains('active');
  },

  async open(reportNo, focusTimeline = false) {
    this.reportNo = reportNo;
    this.focusTimeline = !!focusTimeline;
    document.getElementById('dept-report-no-1').value = reportNo;
    document.getElementById('dept-report-no-2').value = reportNo;

    if (this.subjectEl) this.subjectEl.textContent = 'Loading...';
    if (this.contentEl) this.contentEl.innerHTML = '<div class="text-sm text-muted-foreground">Loading report details...</div>';

    this.overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    try {
      const res = await fetch('api/report.php?id=' + encodeURIComponent(reportNo), { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('Failed to load report (' + res.status + ')');
      const report = await res.json();
      if (report && report.error) throw new Error(report.error);

      if (this.subjectEl) this.subjectEl.textContent = report.subject || 'Department Action';
      if (this.contentEl) this.contentEl.innerHTML = this.render(report);

      if (this.focusTimeline) {
        window.setTimeout(() => {
          const input = document.getElementById('dept-days-input');
          if (input) input.focus();
        }, 0);
      }
    } catch (e) {
      if (this.subjectEl) this.subjectEl.textContent = 'Department Action';
      if (this.contentEl) this.contentEl.innerHTML = '<div class="alert alert-danger">' + (e && e.message ? e.message : 'Unable to load report.') + '</div>';
    }
  },

  close() {
    if (this.overlay) {
      this.overlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  },

  render(report) {
    const attachments = Array.isArray(report.attachments) ? report.attachments : [];
    const attachHtml = attachments.length
      ? '<div class="modal-info-item mt-3"><div class="modal-info-label">Attached Images</div>' +
        '<div class="grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;">' +
        attachments.map(a => {
          const href = a.url || a.file_path || '#';
          const name = a.file_name || 'Attachment';
          return '<a class="btn" style="border: 1px solid var(--border); text-decoration:none; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" href="' + href + '" target="_blank" rel="noopener">' + name + '</a>';
        }).join('') +
        '</div></div>'
      : '<div class="modal-info-item mt-3"><div class="modal-info-label">Attached Images</div><div class="modal-info-value">None</div></div>';

    const statusLabel = (window.ReportModal && ReportModal.getStatusLabel) ? ReportModal.getStatusLabel(report.status) : (report.status || '');
    const sevLabel = (window.ReportModal && ReportModal.getSeverityLabel) ? ReportModal.getSeverityLabel(report.severity) : (report.severity || '');

    return `
      <div class="modal-section">
        <div class="modal-section-title">Report Details</div>
        <div class="modal-info-grid">
          <div class="modal-info-item"><div class="modal-info-label">Report ID</div><div class="modal-info-value font-mono">${report.id || ''}</div></div>
          <div class="modal-info-item"><div class="modal-info-label">Category</div><div class="modal-info-value">${report.category || 'N/A'}</div></div>
          <div class="modal-info-item"><div class="modal-info-label">Location</div><div class="modal-info-value">${report.location || 'N/A'}</div></div>
          <div class="modal-info-item"><div class="modal-info-label">Severity</div><div class="modal-info-value">${sevLabel}</div></div>
          <div class="modal-info-item"><div class="modal-info-label">Department</div><div class="modal-info-value">${report.department || 'N/A'}</div></div>
          <div class="modal-info-item"><div class="modal-info-label">Current Status</div><div class="modal-info-value">${statusLabel}</div></div>
        </div>
        <div class="modal-info-item mt-3">
          <div class="modal-info-label">Description</div>
          <div class="modal-description">${report.details || ''}</div>
        </div>
        <div class="modal-info-grid mt-3">
          <div class="modal-info-item"><div class="modal-info-label">Actions Taken by Security</div><div class="modal-info-value">${report.actionsTaken || 'N/A'}</div></div>
          <div class="modal-info-item"><div class="modal-info-label">Remarks</div><div class="modal-info-value">${report.remarks || 'N/A'}</div></div>
        </div>
        ${attachHtml}
      </div>

      <div class="modal-section">
        <div class="modal-section-title">Department Actions</div>
        <div class="modal-info-item">
          <div class="modal-info-label">Set Timeframe (Days)</div>
          <input id="dept-days-input" class="form-control form-control-sm" type="number" min="1" max="365" placeholder="e.g. 7" />
          <p class="text-xs text-muted-foreground mt-1">Setting a timeline commits your department to a due date. When due date arrives, the system automatically sends this report to Security for final check.</p>
        </div>
      </div>
    `;
  },

  submitTimeline() {
    const input = document.getElementById('dept-days-input');
    const days = input ? parseInt(String(input.value || '0'), 10) : 0;
    if (!days || days < 1 || days > 365) {
      alert('Please enter valid days (1-365).');
      return false;
    }
    document.getElementById('dept-timeline-days').value = String(days);
    return true;
  },

  submitDone() {
  MarkDoneConfirm.open(); // Triggers the custom HTML modal
  return false; // Crucial: prevents the default browser prompt/submission
}
};
const MarkDoneConfirm = {
  overlay: null,
  
  init() {
    this.overlay = document.getElementById('done-confirm-overlay');
  },

  open() {
    // SYNC: Make sure the confirmation form has the report number from the main modal
    const currentReportNo = document.getElementById('dept-report-no-2').value;
    
    if (!currentReportNo) {
        console.error("Report No not found in DepartmentActionModal");
        return;
    }

    if (this.overlay) {
      this.overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  },

  close() {
    if (this.overlay) {
      this.overlay.classList.remove('active');
      if (!DepartmentActionModal.isOpen()) {
         document.body.style.overflow = '';
      }
    }
  },

  confirm() {
    const submitBtn = document.querySelector('.btn-done-confirm');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
    // Explicitly submit the form from the DepartmentActionModal
    document.getElementById('dept-done-form').submit();
  }
};

document.addEventListener('DOMContentLoaded', () => {
  DepartmentActionModal.init();
  MarkDoneConfirm.init();
});
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
