<?php
function getStatusBadge($status)
{
    $badges = [
        'pending' => '<span class="badge-pending">Pending</span>',
        'approved' => '<span class="badge-approved">Approved</span>',
        'in_progress' => '<span class="badge-in-progress">In Progress</span>',
        'done' => '<span class="badge-done">Done</span>',
        'for_checking' => '<span class="badge-checking">For Checking</span>',
        'closed' => '<span class="badge-closed">Closed</span>',
    ];
    return $badges[$status] ?? '<span class="badge-pending">Pending</span>';
}

function formatDateTime($dateString)
{
    return date('M d, Y g:i A', strtotime($dateString));
}
?>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Report History</h1>
            <p class="text-sm text-muted-foreground mb-0">Complete timeline of all security reports</p>
        </div>

        <div class="d-grid gap-3">
            <?php foreach ($mockReports as $idx => $report): ?>
            <div class="timeline-item clickable-timeline" onclick="ReportModal.open('<?php echo htmlspecialchars(
                $report['id'],
            ); ?>')">
                <div class="timeline-icon">
                    <div class="timeline-dot">
                        <i class="bi bi-clock-history" aria-hidden="true" style="font-size: 16px;"></i>
                    </div>
                    <?php if ($idx < count($mockReports) - 1): ?>
                    <div class="timeline-line"></div>
                    <?php endif; ?>
                </div>
                <div class="timeline-content">
                    <div class="bg-card rounded-lg border p-4 mb-2 cursor-pointer hover:bg-accent/50 transition-colors">
                        <div class="d-flex align-items-center justify-content-between mb-2 gap-2 flex-wrap">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="font-mono text-xs text-muted-foreground"><?php echo htmlspecialchars(
                                    $report['id'],
                                ); ?></span>
                                <?php echo getStatusBadge($report['status']); ?>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                <?php echo formatDateTime($report['submittedAt']); ?>
                            </span>
                        </div>
                        <h3 class="font-medium text-foreground text-sm"><?php echo htmlspecialchars(
                            $report['subject'],
                        ); ?></h3>
                        <p class="text-xs text-muted-foreground mt-1">
                            <?php echo htmlspecialchars($report['category']); ?> • <?php echo htmlspecialchars(
     $report['location'],
 ); ?> • <?php echo htmlspecialchars($report['department']); ?>
                        </p>
                        <div class="mt-3">
                            <button class="view-details-btn">
                                <i class="bi bi-eye" aria-hidden="true" style="font-size: 12px;"></i>
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
