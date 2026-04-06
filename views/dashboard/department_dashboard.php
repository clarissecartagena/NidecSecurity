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
        <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Department Dashboard</h1>
        <p class="text-sm text-muted-foreground mb-0">Assigned Department: <?php echo htmlspecialchars(
            $currentUser['department_name'] ?? '',
        ); ?></p>
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

    <div class="row g-4 mb-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="metric-card metric-card-split metric-accent-warning w-100">
          <div class="metric-card-left">
            <div class="metric-card-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
            <div class="metric-card-text">
              <p class="text-sm fw-semibold text-foreground">Pending Assigned</p>
              <p class="text-xs text-muted-foreground">Reports awaiting your action</p>
            </div>
          </div>
          <div class="metric-card-right">
            <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) $stats[
                'pending_assigned'
            ]; ?></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="metric-card metric-card-split metric-accent-info w-100">
          <div class="metric-card-left">
            <div class="metric-card-icon" aria-hidden="true"><i class="bi bi-tools"></i></div>
            <div class="metric-card-text">
              <p class="text-sm fw-semibold text-foreground">Under Fix Timeframe</p>
              <p class="text-xs text-muted-foreground">Active department timelines</p>
            </div>
          </div>
          <div class="metric-card-right">
            <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) $stats[
                'under_timeline'
            ]; ?></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="metric-card metric-card-split metric-accent-success w-100">
          <div class="metric-card-left">
            <div class="metric-card-icon" aria-hidden="true"><i class="bi bi-check-circle"></i></div>
            <div class="metric-card-text">
              <p class="text-sm fw-semibold text-foreground">Marked as Done</p>
              <p class="text-xs text-muted-foreground">Fixed & awaiting verification</p>
            </div>
          </div>
          <div class="metric-card-right">
            <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) $stats['marked_done']; ?></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="metric-card metric-card-split metric-accent-destructive w-100">
          <div class="metric-card-left">
            <div class="metric-card-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></div>
            <div class="metric-card-text">
              <p class="text-sm fw-semibold text-foreground">Awaiting Final Check</p>
              <p class="text-xs text-muted-foreground">Waiting for security review</p>
            </div>
          </div>
          <div class="metric-card-right">
            <div class="metric-card-value fs-2 fw-bold text-foreground"><?php echo (int) $stats[
                'waiting_final_check'
            ]; ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">

        <!-- Recent Assigned Reports col-8 -->
        <div class="col-lg-8">
            <div class="table-container table-card" style="--table-accent: var(--warning)">
                <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                        <h3 class="font-semibold text-foreground">Recent Assigned Reports</h3>
                        <p class="text-xs text-muted-foreground">Latest reports assigned to your department</p>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(
                        app_url('department-history.php'),
                    ); ?>">View all</a>
                </div>
                <?php if (!empty($recent)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Severity</th>
                                    <th>Date Assigned</th>
                                    <th>Timeline</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $r): ?>
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
                                        <td><span class="badge <?php echo $sevBadge; ?>"><?php echo htmlspecialchars(
    severity_label($r['severity']),
); ?></span></td>
                                        <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                            date('M d, Y', strtotime($r['assigned_at'])),
                                        ); ?></td>
                                        <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                            dept_timeline_status_label(
                                                $r['action_type'] ?? null,
                                                $r['timeline_due'] ?? ($r['fix_due_date'] ?? null),
                                            ),
                                        ); ?></td>
                                        <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                            report_status_label($r['status']),
                                        ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center" style="min-height: 220px;">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: hsl(var(--muted-foreground));"></i>
                        <p class="fw-semibold mt-3 mb-1 text-foreground">No Reports Yet</p>
                        <p class="text-xs text-muted-foreground">No reports have been assigned to your department yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Needs Action (no timeframe) col-4 -->
        <div class="col-lg-4">
            <div class="table-container table-card h-100" style="--table-accent: var(--destructive)">
                <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="font-semibold text-foreground">Needs Action</h3>
                        <p class="text-xs text-muted-foreground">Reports with no timeframe set</p>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="<?php echo htmlspecialchars(
                        app_url('department-action.php'),
                    ); ?>">Set timeframes</a>
                </div>
                <?php if (!empty($needsAction)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Severity</th>
                                    <th>Assigned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($needsAction as $r): ?>
                                    <?php
                                    $sevRaw = strtolower((string) ($r['severity'] ?? ''));
                                    $naBadge = match ($sevRaw) {
                                        'critical' => 'badge--destructive',
                                        'high' => 'badge--warning',
                                        'medium' => 'badge--info',
                                        default => 'badge--muted',
                                    };
                                    ?>
                                    <tr class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
                                        $r['report_no'],
                                    ); ?>')">
                                        <td class="font-medium text-truncate" style="max-width:120px;" title="<?php echo htmlspecialchars(
                                            $r['subject'],
                                        ); ?>"><?php echo htmlspecialchars($r['subject']); ?></td>
                                        <td><span class="badge <?php echo $naBadge; ?>"><?php echo htmlspecialchars(
    severity_label($r['severity']),
); ?></span></td>
                                        <td class="text-muted-foreground text-xs"><?php echo htmlspecialchars(
                                            isset($r['assigned_at']) ? date('M d', strtotime($r['assigned_at'])) : '—',
                                        ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center" style="min-height: 220px;">
                        <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: var(--success);"></i>
                        <p class="fw-semibold mt-3 mb-1 text-foreground">All Set!</p>
                        <p class="text-xs text-muted-foreground">Every assigned report already has a timeframe. Great work!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.row -->
  </div>
</main>

<script>
  window.NIDEC_ROLE = 'department';
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
