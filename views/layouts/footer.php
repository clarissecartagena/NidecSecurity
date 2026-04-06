<?php
// Layout footer
?>
<!-- Report Details Modal -->
<div id="report-modal-overlay" class="modal-overlay">
    <div id="report-modal" class="report-modal">
        <div class="report-modal-header">
            <div>
                <h3 id="modal-report-subject">Report Details</h3>
                <p class="modal-header-subtitle" id="modal-report-no"></p>
            </div>
            <button class="modal-close-btn" type="button" aria-label="Close" onclick="ReportModal.close()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="report-modal-body" id="modal-report-content">
            <!-- Content populated by JavaScript -->
        </div>
        <!-- Notes textarea shown when an action requires a reason/notes -->
        <div id="modal-notes-area" style="display:none; padding: 0.75rem 1rem 0; border-top: 1px solid var(--border);">
            <label for="modal-action-notes-input" id="modal-notes-label" class="form-label text-sm fw-semibold mb-1">Notes / Reason <span class="text-danger">*</span></label>
            <textarea id="modal-action-notes-input" class="form-control form-control-sm" rows="2" placeholder="Enter reason…" style="resize:vertical; min-height:56px;"></textarea>
        </div>
        <div class="report-modal-footer">
            <!-- Role-specific action buttons (populated by JS) -->
            <div id="modal-action-buttons" style="display:contents;"></div>
            <button id="modal-copy-link" class="btn btn-outline-secondary" type="button" title="Copy shareable link" disabled>
                <i class="bi bi-link-45deg" aria-hidden="true"></i>
            </button>
            <button id="modal-view-pdf" class="btn btn-outline-primary" type="button" disabled>
                <i class="bi bi-eye" aria-hidden="true"></i> View PDF
            </button>
            <button id="modal-download-pdf" class="btn btn-primary" type="button" disabled>Download PDF</button>
            <button class="btn btn-outline-secondary" type="button" onclick="ReportModal.close()">Close</button>
        </div>
    </div>
</div>

<!-- Hidden POST form used by dashboard action buttons -->
<form id="modal-action-form" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
        function_exists('csrf_token') ? csrf_token() : $_SESSION['csrf_token'] ?? '',
    ); ?>" />
    <input type="hidden" name="report_no" id="modal-action-report-no" value="" />
    <input type="hidden" name="action"    id="modal-action-name"    value="" />
    <input type="hidden" name="notes"         id="modal-action-notes-hidden"         value="" />
    <input type="hidden" name="final_remarks" id="modal-action-final-remarks-hidden" value="" />
</form>
<script src="<?php echo htmlspecialchars(app_url('assets/js/app.js')); ?>?v=<?php echo time(); ?>"></script>
</body>
</html>
