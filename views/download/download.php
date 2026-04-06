<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-download me-2 text-primary"></i>Download Reports</h1>
            <p class="text-sm text-muted-foreground mb-0">Export security reports in various formats</p>
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($reports as $r): ?>
            <div class="col-12 col-md-4">
            <div class="bg-card rounded-lg border p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-file-earmark-text text-primary" aria-hidden="true" style="font-size: 20px;"></i>
                    <h3 class="font-semibold text-foreground"><?php echo htmlspecialchars($r['label']); ?></h3>
                </div>
                <p class="text-sm text-muted-foreground mb-1"><?php echo htmlspecialchars($r['desc']); ?></p>
                <div class="d-flex align-items-center gap-1 text-xs text-muted-foreground mb-3">
                    <i class="bi bi-calendar-event" aria-hidden="true" style="font-size: 12px;"></i>
                    <?php echo htmlspecialchars($r['date']); ?>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-fill text-sm">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true" style="font-size: 14px;"></i>
                        PDF
                    </button>
                    <button class="btn btn-secondary flex-fill text-sm">
                        <i class="bi bi-filetype-csv" aria-hidden="true" style="font-size: 14px;"></i>
                        CSV
                    </button>
                </div>
            </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-card rounded-lg border p-4">
            <h3 class="font-semibold text-foreground mb-3">Custom Date Range</h3>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-4">
                    <label class="form-label text-sm text-muted-foreground mb-1">Start Date</label>
                    <input type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-12 col-sm-4">
                    <label class="form-label text-sm text-muted-foreground mb-1">End Date</label>
                    <input type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-12 col-sm-4">
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-download" aria-hidden="true"></i>
                        Generate Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
