<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>All Reports</h1>
            <p class="text-sm text-muted-foreground mb-0">All incident reports you created</p>
        </div>

        <div class="table-container table-card" style="--table-accent: var(--primary)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Reports</h3>
                    <p class="text-xs text-muted-foreground">Click a row to view full details</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int) count($reports); ?></div>
            </div>
            <div class="table-responsive">
            <table id="reports-table" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Severity</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="7" class="text-center text-muted-foreground">No reports found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr data-status="<?php echo htmlspecialchars(
                                $r['status'],
                            ); ?>" class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars(
    $r['report_no'],
); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars(
                                    $r['report_no'],
                                ); ?></td>
                                <td class="font-medium text-truncate" style="max-width: 240px;"><?php echo htmlspecialchars(
                                    $r['subject'],
                                ); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($r['category']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                    severity_label($r['severity']),
                                ); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                    $r['department_name'],
                                ); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(
                                    report_status_label($r['status']),
                                ); ?></td>
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
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
