<?php
function dept_timeline_status_label(?string $actionType, ?string $timelineDue): string
{
    if ($actionType === 'done') {
        return 'DONE';
    }
    if ($actionType === 'timeline') {
        if (!$timelineDue) {
            return 'TIMELINE';
        }
        $due = strtotime($timelineDue);
        if ($due !== false && $due <= time()) {
            return 'DUE';
        }
        return 'ON TRACK';
    }
    return 'NOT SET';
} ?>

<main class="main-content">
  <div class="animate-fade-in">
    <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Report History</h1>
        <p class="text-sm text-muted-foreground mb-0">All reports assigned to your department</p>
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

    <div class="table-container table-card" style="--table-accent: var(--info);">
      <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <h3 class="font-semibold text-foreground">Report History</h3>
          <p class="text-xs text-muted-foreground">All reports assigned to your department</p>
        </div>
        <div class="text-xs text-muted-foreground">Total: <?php echo (int) count($rows); ?></div>
      </div>
      <div class="table-responsive">
        <table id="dept-history-table" class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Report ID</th>
            <th>Subject</th>
            <th>Severity</th>
            <th>Location</th>
            <th>Date Assigned</th>
            <th>Timeline Status</th>
            <th>Current Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted-foreground">No reports found.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($r['report_no']); ?>')">
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
                    dept_timeline_status_label($r['action_type'] ?? null, $r['timeline_due'] ?? null),
                ); ?></td>
                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                    report_status_label($r['status']),
                ); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

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
