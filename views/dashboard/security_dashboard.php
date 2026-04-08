


<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-shield-fill me-2 text-primary"></i>Security Dashboard</h1>
            <p class="text-sm text-muted-foreground mb-0">Welcome back, <?= htmlspecialchars(
                $currentUser['name'] ?? ($currentUser['username'] ?? 'Officer'),
            ) ?>.</p>
        </div>




    
        <!-- Metric Cards -->
        <div class="row g-3 mb-4 security-metric-grid">
            <div class="col-sm-6 col-xl-3">
                <?php $submittedToday = (int) $stats['submitted_today']; ?>
                <button type="button" class="metric-card security-metric-card metric-accent-info w-100 p-3">
                    <i class="bi bi-send security-metric-overlay-icon" aria-hidden="true"></i>
                    <div class="d-flex justify-content-end">
                        <div class="security-metric-value text-foreground <?= $submittedToday === 0 ? 'value-zero' : '' ?>"><?= $submittedToday ?></div>
                    </div>
                    <div class="security-metric-footer">
                        <p class="text-sm fw-semibold text-foreground">Today's Reports</p>
                        <p class="text-xs text-muted-foreground">Submitted today</p>
                    </div>
                </button>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?php $waitingGaReview = (int) $stats['waiting_ga_review']; ?>
                <button type="button" class="metric-card security-metric-card metric-accent-warning w-100 p-3">
                    <i class="bi bi-clock security-metric-overlay-icon" aria-hidden="true"></i>
                    <div class="d-flex justify-content-end">
                        <div class="security-metric-value text-foreground <?= $waitingGaReview === 0 ? 'value-zero' : '' ?>"><?= $waitingGaReview ?></div>
                    </div>
                    <div class="security-metric-footer">
                        <p class="text-sm fw-semibold text-foreground">Pending GA Review</p>
                        <p class="text-xs text-muted-foreground">Awaiting GA staff</p>
                    </div>
                </button>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?php $waitingFinalCheck = (int) $stats['waiting_final_check']; ?>
                <button type="button" class="metric-card security-metric-card metric-accent-destructive w-100 p-3">
                    <i class="bi bi-shield-check security-metric-overlay-icon" aria-hidden="true"></i>
                    <div class="d-flex justify-content-end">
                        <div class="security-metric-value text-foreground <?= $waitingFinalCheck === 0 ? 'value-zero' : '' ?>"><?= $waitingFinalCheck ?></div>
                    </div>
                    <div class="security-metric-footer">
                        <p class="text-sm fw-semibold text-foreground">Final Check</p>
                        <p class="text-xs text-muted-foreground">Needs your final sign-off</p>
                    </div>
                </button>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?php $resolvedCount = (int) $stats['resolved']; ?>
                <button type="button" class="metric-card security-metric-card metric-accent-success w-100 p-3">
                    <i class="bi bi-check-circle security-metric-overlay-icon" aria-hidden="true"></i>
                    <div class="d-flex justify-content-end">
                        <div class="security-metric-value text-foreground <?= $resolvedCount === 0 ? 'value-zero' : '' ?>"><?= $resolvedCount ?></div>
                    </div>
                    <div class="security-metric-footer">
                        <p class="text-sm fw-semibold text-foreground">Resolved</p>
                        <p class="text-xs text-muted-foreground">Fully resolved reports</p>
                    </div>
                </button>
            </div>
        </div>





        <!-- Content Row: Recent Reports (70%) + Final Checking (30%) -->
        <div class="row g-3">

            <!-- Recent Reports -->
            <div class="col-lg-8">
                <div class="table-container table-card" style="--table-accent: var(--info)">
                    <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h3 class="font-semibold text-foreground">Recent Reports</h3>
                            <p class="text-xs text-muted-foreground">Latest 5 reports you submitted</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Department</th>
                                    <th>Severity</th>
                                    <th>Date Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent)): ?>
                                    <tr><td colspan="4" class="text-center text-muted-foreground py-4">No reports found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent as $r): ?>
                                        <tr class="clickable-row" onclick="ReportModal.open('<?= htmlspecialchars(
                                            $r['report_no'],
                                        ) ?>')">
                                            <td class="font-medium text-truncate" style="max-width:240px;"><?= htmlspecialchars(
                                                $r['subject'],
                                            ) ?></td>
                                            <td class="text-muted-foreground"><?= htmlspecialchars(
                                                $r['department_name'],
                                            ) ?></td>
                                            <td>
                                                <?php $sevBadge = match (strtolower($r['severity'] ?? '')) {
                                                    'critical' => 'badge--destructive',
                                                    'high' => 'badge--warning',
                                                    'medium' => 'badge--info',
                                                    'low' => 'badge--success',
                                                    default => 'badge--muted',
                                                }; ?>
                                                <span class="badge <?= $sevBadge ?>"><?= htmlspecialchars(
    severity_label($r['severity']),
) ?></span>
                                            </td>
                                            <td class="text-muted-foreground text-xs"><?= htmlspecialchars(
                                                date('M d, Y', strtotime($r['submitted_at'])),
                                            ) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Final Checking Panel -->
            <div class="col-lg-4">
                <div class="table-container table-card security-quick-action-card mb-3" style="--table-accent: var(--primary)">
                    <div class="px-3 pt-3 pb-2 border-b d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="quick-action-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                            <h3 class="font-semibold text-foreground mb-0">Quick Action</h3>
                        </div>
                    </div>
                    <div class="p-3">
                        <p class="text-xs text-muted-foreground mb-3">Need to file something now? Submit a new security report in one step.</p>
                        <a class="btn btn-primary w-100" href="<?= htmlspecialchars(app_url('submit-report.php')) ?>">
                            <i class="bi bi-plus-circle me-1"></i> Submit Security Report
                        </a>
                    </div>
                </div>

                <div class="table-container table-card" style="--table-accent: var(--destructive)">
                    <div class="px-3 pt-3 pb-2 border-b">
                        <h3 class="font-semibold text-foreground">Final Checking</h3>
                        <p class="text-xs text-muted-foreground">Reports awaiting your sign-off</p>
                    </div>
                    <?php if (!empty($finalChecks)): ?>
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
                                    <?php foreach ($finalChecks as $fc): ?>
                                        <tr class="clickable-row" onclick="ReportModal.open('<?= htmlspecialchars(
                                            $fc['report_no'],
                                        ) ?>')">
                                            <td class="font-medium text-truncate" style="max-width:140px;" title="<?= htmlspecialchars(
                                                $fc['subject'],
                                            ) ?>"><?= htmlspecialchars($fc['subject']) ?></td>
                                            <td>
                                                <?php $fcBadge = match (strtolower($fc['severity'] ?? '')) {
                                                    'critical' => 'badge--destructive',
                                                    'high' => 'badge--warning',
                                                    'medium' => 'badge--info',
                                                    'low' => 'badge--success',
                                                    default => 'badge--muted',
                                                }; ?>
                                                <span class="badge <?= $fcBadge ?>"><?= htmlspecialchars(
    severity_label($fc['severity']),
) ?></span>
                                            </td>
                                            <td class="text-muted-foreground text-xs"><?= htmlspecialchars(
                                                date('M d', strtotime($fc['submitted_at'])),
                                            ) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center" style="min-height: 220px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: var(--success);"></i>
                            <p class="fw-semibold mt-3 mb-1 text-foreground">All Clear</p>
                            <p class="text-xs text-muted-foreground">No reports are currently awaiting your final check.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.row -->
    </div>
</main>

<script>window.NIDEC_ROLE = 'security';</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
