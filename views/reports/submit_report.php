<style>
/* ---- Submit Report page overrides ---- */
.sr-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.625rem;
    margin-top: 0.75rem;
}
.sr-preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: hsl(var(--muted));
    border: 1px solid hsl(var(--border));
}
.sr-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.sr-preview-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0,0,0,0.65);
    border: none;
    color: #fff;
    font-size: 13px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    transition: background 0.15s;
}
.sr-preview-remove:hover { background: rgba(220,38,38,0.9); }
.sr-dropzone {
    border: 2px dashed hsl(var(--border));
    border-radius: 10px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: hsl(var(--muted) / 0.35);
}
.sr-dropzone:hover, .sr-dropzone.drag-over {
    border-color: hsl(var(--success));
    background: hsl(var(--success) / 0.06);
}
.sr-dropzone i { font-size: 2rem; color: hsl(var(--muted-foreground)); display: block; margin-bottom: 0.4rem; }
.sr-empty-hint { font-size: 0.82rem; color: hsl(var(--muted-foreground)); margin-top: 0.35rem; }
.optional-tag {
    font-size: 0.72rem;
    font-weight: 400;
    color: hsl(var(--muted-foreground));
    margin-left: 0.3rem;
    font-style: italic;
}
.submit-split-row {
    align-items: flex-start;
}
.preview-sticky {
    position: sticky;
    top: 16px;
}
.preview-panel {
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    background: hsl(var(--card));
    padding: 12px;
}
.preview-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
}
.preview-sheet {
    background: #fff;
    border: 1px solid hsl(var(--border));
    border-radius: 8px;
    padding: 28px 26px 24px 26px;
}
.preview-title {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: hsl(var(--muted-foreground));
    font-weight: 700;
    margin: 0;
}
.preview-expand-btn {
    white-space: nowrap;
}
.preview-fullscreen-modal .modal-content {
    background: hsl(var(--muted) / 0.25);
}
.preview-fullscreen-body {
    padding: 16px;
    overflow: auto;
}
.preview-fullscreen-sheet {
    max-width: 1080px;
    margin: 0 auto;
}
.preview-header-line {
    text-align: center;
    margin: 0;
    line-height: 1.22;
}
.preview-header-line--h1 { font-weight: 700; font-size: 0.98rem; }
.preview-header-line--h2 { font-weight: 700; font-size: 0.9rem; }
.preview-header-line--h3 { font-size: 0.8rem; }
.preview-header-line--space-sm { margin-top: 1px; }
.preview-header-line--space-md { margin-top: 4px; }

.preview-memo-title {
    margin: 18px 0 14px 0;
    text-align: center;
    font-weight: 700;
    letter-spacing: 0.02em;
    font-size: 0.9rem;
}

.preview-doc {
    min-height: 740px;
    display: flex;
    flex-direction: column;
}

.preview-meta-table {
    margin-top: 30px;
    font-size: 0.84rem;
}

.preview-meta-table--external {
    margin-top: 26px;
}

.preview-meta-row {
    display: grid;
    grid-template-columns: 84px 12px 1fr;
    gap: 0;
    margin-bottom: 10px;
    align-items: start;
}

.preview-meta-row--internal {
    grid-template-columns: 96px 12px 1fr;
}

.preview-meta-row--tight {
    margin-bottom: 7px;
}

.preview-meta-k {
    font-weight: 700;
    text-transform: uppercase;
}

.preview-meta-c {
    text-align: center;
    font-weight: 700;
}

.preview-subject {
    font-weight: 700;
}

.preview-divider {
    margin: 20px 0 20px 0;
    border-top: 1px solid #5b5b5b;
}

.preview-block {
    margin-top: 16px;
}
.preview-block-label {
    font-size: 0.82rem;
    font-weight: 700;
    margin-bottom: 5px;
}
.preview-block-value {
    white-space: pre-wrap;
    font-size: 0.84rem;
    min-height: 1.3rem;
    line-height: 1.35;
    padding-left: 16px;
}

.preview-evidence-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
}

.preview-evidence-item {
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: hsl(var(--muted));
}

.preview-evidence-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.preview-missing {
    color: #6b7280;
    font-style: italic;
}

.preview-signatory-wrap {
    margin-top: auto;
    padding-top: 54px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
}

.preview-signatory-label {
    font-size: 0.82rem;
}

.preview-signature-wrap {
    margin-top: 4px;
    margin-bottom: 3px;
    min-height: 44px;
}

.preview-signature-img {
    max-height: 44px;
    max-width: 180px;
    width: auto;
    height: auto;
    object-fit: contain;
    display: block;
}

.preview-signatory-name {
    margin-top: 2px;
    font-weight: 700;
    font-size: 0.84rem;
    text-transform: uppercase;
}

.preview-signatory-line {
    margin-top: 1px;
    font-size: 0.82rem;
}

.preview-brand-internal .preview-aragon-red {
    color: #991b1b;
    font-weight: 700;
}

.preview-brand-internal .preview-aragon-blue {
    color: #1d4ed8;
    font-weight: 700;
}

/* Ensure submit-page overlays always cover full viewport edge-to-edge */
.modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw;
    height: 100vh;
    z-index: 5000 !important;
}

.modal-overlay.active {
    display: flex !important;
}

body.modal-open-frozen {
    overflow: hidden !important;
}
</style>

<main class="main-content">
    <div class="animate-fade-in">

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> mb-4" role="alert">
                <?= htmlspecialchars($flash) ?>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-send-fill me-2 text-primary"></i>Submit Security Report</h1>
            <p class="text-sm text-muted-foreground mb-0">Create a new security incident report</p>
        </div>

        <div class="row g-4 submit-split-row">
        <div class="col-12 col-xl-6">

        <form id="submit-form" method="POST" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />

            <!-- ── Incident Details ── -->
            <div class="section-card section-accent-info mb-4">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <h2 class="h6 fw-bold text-foreground mb-1">Incident Details</h2>
                        <p class="text-sm text-muted-foreground mb-0">Fill in the key incident information to route the report correctly.</p>
                    </div>
                    <span class="badge badge--info">Security Report</span>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="report_type" class="form-label">Report Type <span class="text-danger">*</span></label>
                        <select id="report_type" name="security_type" class="form-select" required>
                            <option value="">Select report type</option>
                            <option value="internal" <?= ($_POST['security_type'] ?? '') === 'internal'
                                ? 'selected'
                                : '' ?>>Internal</option>
                            <option value="external" <?= ($_POST['security_type'] ?? '') === 'external'
                                ? 'selected'
                                : '' ?>>External</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="entity" class="form-label">Entity / Building <span class="text-danger">*</span></label>
                        <select id="entity" name="building" class="form-select" required>
                            <option value="">Select entity</option>
                            <option value="NCFL" <?= ($_POST['building'] ?? '') === 'NCFL'
                                ? 'selected'
                                : '' ?>>NCFL</option>
                            <option value="NPFL" <?= ($_POST['building'] ?? '') === 'NPFL'
                                ? 'selected'
                                : '' ?>>NPFL</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select id="subject_id" name="subject_id" class="form-select" required 
                                data-selected="<?= (int) ($_POST['subject_id'] ?? 0) ?>"
                                onchange="handleDropdownChange(this, 'category', 'category_id')">
                            <option value="" disabled <?= (int) ($_POST['subject_id'] ?? 0) <= 0
                                ? 'selected'
                                : '' ?>>Select Subject</option>
                            <?php if (isset($initialSubjects)):
                                foreach ($initialSubjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (int) ($_POST['subject_id'] ?? 0) === (int) $s['id']
    ? 'selected'
    : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach;
                            endif; ?>
                            <option value="add_new" class="text-primary fw-bold">+ Add New Subject...</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required disabled
                                data-selected="<?= (int) ($_POST['category_id'] ?? 0) ?>"
                                onchange="handleDropdownChange(this, 'sub_category', 'sub_category_id')">
                            <option value="">Select Subject First</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="sub_category_id" class="form-label">Sub-Category <span class="text-danger">*</span></label>
                        <select id="sub_category_id" name="sub_category_id" class="form-select" required disabled
                                data-selected="<?= (int) ($_POST['sub_category_id'] ?? 0) ?>"
                                onchange="handleDropdownChange(this, 'final', null)">
                            <option value="">Select Category First</option>
                        </select>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <input type="text" id="location" name="location" required
                            placeholder="e.g. Building A - 2nd Floor"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" />
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="severity" class="form-label">Severity Level <span class="text-danger">*</span></label>
                        <select id="severity" name="severity" class="form-select" required>
                            <?php foreach ($severityLevels as $level): ?>
                            <option value="<?= $level ?>" <?= ($_POST['severity'] ?? 'medium') === $level
    ? 'selected'
    : '' ?>><?= ucfirst($level) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                        <select id="department" name="department_id" class="form-select" required>
                            <option value="">Select department</option>
                            <?php foreach ($departmentsDb ?? [] as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>" <?= (int) ($_POST['department_id'] ?? 0) ===
(int) $dept['id']
    ? 'selected'
    : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <!-- ── Narrative ── -->
            <div class="section-card section-accent-primary mb-4">
                <div class="mb-3">
                    <h2 class="h6 fw-bold text-foreground mb-1">Narrative</h2>
                    <p class="text-sm text-muted-foreground mb-0">Describe what happened and what has already been done.</p>
                </div>

                <div class="d-grid gap-3">
                    <div>
                        <label for="details" class="form-label">Full Details <span class="text-danger">*</span></label>
                        <textarea id="details" name="details" required rows="4" class="form-control"
                            placeholder="Provide a detailed description of the incident..."><?= htmlspecialchars(
                                $_POST['details'] ?? '',
                            ) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="assessment" class="form-label">Assessment <span class="text-danger">*</span></label>
                            <textarea id="assessment" name="assessment" required rows="3" class="form-control"
                                placeholder="Your assessment of the situation..."><?= htmlspecialchars(
                                    $_POST['assessment'] ?? '',
                                ) ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="recommendations" class="form-label">Recommendations <span class="text-danger">*</span></label>
                            <textarea id="recommendations" name="recommendations" required rows="3" class="form-control"
                                placeholder="Recommended corrective actions..."><?= htmlspecialchars(
                                    $_POST['recommendations'] ?? '',
                                ) ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="actions-taken" class="form-label">Actions Taken <span class="optional-tag">(Optional)</span></label>
                            <textarea id="actions-taken" name="actions_taken" rows="3" class="form-control"
                                placeholder="Describe actions already taken..."><?= htmlspecialchars(
                                    $_POST['actions_taken'] ?? '',
                                ) ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="remarks" class="form-label">Remarks <span class="optional-tag">(Optional)</span></label>
                            <textarea id="remarks" name="remarks" rows="3" class="form-control"
                                placeholder="Any additional remarks..."><?= htmlspecialchars(
                                    $_POST['remarks'] ?? '',
                                ) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Attachments ── -->
            <div class="section-card section-accent-success">
                <div class="mb-3">
                    <h2 class="h6 fw-bold text-foreground mb-1">Attachments <span class="optional-tag" style="font-size:0.78rem;">(Optional)</span></h2>
                    <p class="text-sm text-muted-foreground mb-0">Upload evidence images — PNG or JPG, up to 10 MB each. Multiple files allowed.</p>
                </div>

                <!-- Hidden real input -->
                <input id="evidence" name="evidence[]" type="file" accept="image/png,image/jpeg" multiple style="display:none;" />

                <!-- Drop zone -->
                <div id="evidence-dropzone" class="sr-dropzone">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <p class="text-sm text-muted-foreground mb-0">Click to browse or drag &amp; drop images here</p>
                    <p class="sr-empty-hint">PNG, JPG &mdash; up to 10 MB each</p>
                </div>

                <!-- Preview grid -->
                <div id="sr-preview-grid" class="sr-preview-grid" style="display:none;"></div>

                <div class="d-flex align-items-center justify-content-between pt-3 mt-1 gap-3 flex-wrap">
                    <p id="sr-file-count" class="text-xs text-muted-foreground mb-0"></p>
                    <button id="submit-report-btn" type="button" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        Submit Report
                    </button>
                </div>
            </div>
        </form>
        </div>

        <div class="col-12 col-xl-6">
            <div class="preview-sticky">
                <div class="preview-panel">
                    <div class="preview-toolbar">
                        <p class="preview-title">Live Template Preview</p>
                        <button type="button" id="open-fullscreen-preview" class="btn btn-sm btn-outline-secondary preview-expand-btn" title="Open fullscreen preview" aria-label="Open fullscreen preview">
                            <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="preview-sheet">
                        <?php
                        $previewOfficerNameSource = trim((string) ($currentUser['name'] ?? ''));
                        $previewOfficerSignaturePath = trim((string) ($currentUser['signature_path'] ?? ''));
                        if ($previewOfficerNameSource === '' && function_exists('getUser')) {
                            $u = getUser();
                            $previewOfficerNameSource = trim((string) ($u['name'] ?? ''));
                            if ($previewOfficerSignaturePath === '') {
                                $previewOfficerSignaturePath = trim((string) ($u['signature_path'] ?? ''));
                            }

                            if ($previewOfficerNameSource === '') {
                                $sessionEmployeeNo = trim((string) ($u['employee_no'] ?? ''));
                                if ($sessionEmployeeNo !== '') {
                                    $row = db_fetch_one(
                                        'SELECT name, signature_path FROM users WHERE employee_no = ? LIMIT 1',
                                        's',
                                        [$sessionEmployeeNo],
                                    );
                                    $previewOfficerNameSource = trim((string) ($row['name'] ?? ''));
                                    if ($previewOfficerSignaturePath === '') {
                                        $previewOfficerSignaturePath = trim((string) ($row['signature_path'] ?? ''));
                                    }
                                }

                                if ($previewOfficerNameSource === '') {
                                    $sessionUsername = trim((string) ($u['username'] ?? ''));
                                    if ($sessionUsername !== '') {
                                        $row = db_fetch_one(
                                            'SELECT name, signature_path FROM users WHERE username = ? LIMIT 1',
                                            's',
                                            [$sessionUsername],
                                        );
                                        $previewOfficerNameSource = trim((string) ($row['name'] ?? ''));
                                        if ($previewOfficerSignaturePath === '') {
                                            $previewOfficerSignaturePath = trim(
                                                (string) ($row['signature_path'] ?? ''),
                                            );
                                        }
                                    }
                                }
                            }
                        }
                        $previewOfficerName = strtoupper(
                            $previewOfficerNameSource !== '' ? $previewOfficerNameSource : 'OFFICER NAME',
                        );
                        $previewOfficerSignatureUrl = '';
                        if ($previewOfficerSignaturePath !== '') {
                            $previewOfficerSignatureUrl = app_url(
                                ltrim(str_replace('\\', '/', $previewOfficerSignaturePath), '/'),
                            );
                        }
                        ?>
                        <div id="preview-template-internal" class="preview-doc d-none">
                            <div class="preview-brand-internal">
                                <p class="preview-header-line preview-header-line--h1">
                                    <span class="preview-aragon-red">ARAGON</span>
                                    <span class="preview-aragon-blue"> SECURITY AND INVESTIGATION</span>
                                </p>
                                <p class="preview-header-line preview-header-line--h2 preview-aragon-blue">AGENCY, CORPORATION</p>
                                <p class="preview-header-line preview-header-line--h3 preview-header-line--space-sm">NIDEC PHILIPPINES CORPORATION DETACHMENT</p>
                                <p class="preview-header-line preview-header-line--h3 preview-header-line--space-sm">136 North Science Avenue Extension, Laguna Technopark, Binan, Laguna</p>
                            </div>

                            <div class="preview-meta-table preview-meta-table--internal">
                                <div class="preview-meta-row preview-meta-row--internal">
                                    <div class="preview-meta-k">FOR</div><div class="preview-meta-c">:</div><div id="preview-internal-for">DEPARTMENT PIC</div>
                                </div>
                                <div class="preview-meta-row preview-meta-row--internal preview-meta-row--tight">
                                    <div class="preview-meta-k">THRU</div><div class="preview-meta-c">:</div><div id="preview-internal-thru">GA MANAGER</div>
                                </div>
                                <div class="preview-meta-row preview-meta-row--internal">
                                    <div class="preview-meta-k">SUBJECT</div><div class="preview-meta-c">:</div><div id="preview-internal-subject" class="preview-subject">REPORT</div>
                                </div>
                                <div class="preview-meta-row preview-meta-row--internal">
                                    <div class="preview-meta-k">DATE</div><div class="preview-meta-c">:</div><div id="preview-internal-date"><?= htmlspecialchars(
                                        strtoupper(date('d F Y')),
                                    ) ?></div>
                                </div>
                            </div>

                            <div class="preview-divider"></div>

                            <div class="preview-block">
                                <div class="preview-block-label">Details:</div>
                                <div id="preview-internal-details" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div class="preview-block">
                                <div class="preview-block-label">Assessment:</div>
                                <div id="preview-internal-assessment" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div class="preview-block">
                                <div class="preview-block-label">Recommendations:</div>
                                <div id="preview-internal-recommendations" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div id="preview-internal-actions-block" class="preview-block">
                                <div class="preview-block-label">Action Taken:</div>
                                <div id="preview-internal-actions" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div id="preview-internal-remarks-block" class="preview-block">
                                <div class="preview-block-label">Remarks:</div>
                                <div id="preview-internal-remarks" class="preview-block-value preview-missing">—</div>
                            </div>

                            <div class="preview-block">
                                <div class="preview-block-label">Evidence / Attachments:</div>
                                <div id="preview-evidence-grid-internal" class="preview-evidence-grid"></div>
                            </div>

                            <div class="preview-signatory-wrap">
                                <div>
                                    <div class="preview-signatory-label">Prepared by:</div>
                                    <?php if ($previewOfficerSignatureUrl !== ''): ?>
                                        <div class="preview-signature-wrap">
                                            <img src="<?= htmlspecialchars(
                                                $previewOfficerSignatureUrl,
                                            ) ?>" alt="Officer signature" class="preview-signature-img">
                                        </div>
                                    <?php endif; ?>
                                    <div id="preview-internal-prepared-name" class="preview-signatory-name"><?= htmlspecialchars(
                                        $previewOfficerName,
                                    ) ?></div>
                                    <div id="preview-internal-prepared-line1" class="preview-signatory-line">NCFL / Security Officer</div>
                                    <div class="preview-signatory-line">Internal Security</div>
                                </div>
                            </div>
                        </div>

                        <div id="preview-template-external" class="preview-doc">
                            <p class="preview-header-line preview-header-line--h1">SISCO INVESTIGATION &amp; SECURITY CORPORATION</p>
                            <p class="preview-header-line preview-header-line--h2 preview-header-line--space-md">NIDEC Philippines Corporation - Security Detachment</p>
                            <p class="preview-header-line preview-header-line--h3 preview-header-line--space-sm">119 Technology Avenue Special Economic Zone Laguna Technopark, Binan Laguna</p>

                            <div class="preview-meta-table preview-meta-table--external">
                                <div class="preview-meta-row">
                                    <div class="preview-meta-k">DATE</div><div class="preview-meta-c">:</div><div id="preview-external-date"><?= htmlspecialchars(
                                        date('d F Y'),
                                    ) ?></div>
                                </div>
                                <div class="preview-meta-row" style="margin-top:14px;">
                                    <div class="preview-meta-k">TO</div><div class="preview-meta-c">:</div><div id="preview-external-to">DEPARTMENT PIC</div>
                                </div>
                                <div class="preview-meta-row">
                                    <div class="preview-meta-k">THRU</div><div class="preview-meta-c">:</div><div id="preview-external-thru">GA MANAGER</div>
                                </div>
                                <div class="preview-meta-row">
                                    <div class="preview-meta-k">SUBJECT</div><div class="preview-meta-c">:</div><div id="preview-external-subject" class="preview-subject">REPORT</div>
                                </div>
                            </div>

                            <div class="preview-divider"></div>

                            <div class="preview-block">
                                <div class="preview-block-label">Details:</div>
                                <div id="preview-external-details" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div class="preview-block">
                                <div class="preview-block-label">Assessment:</div>
                                <div id="preview-external-assessment" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div class="preview-block">
                                <div class="preview-block-label">Recommendations:</div>
                                <div id="preview-external-recommendations" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div id="preview-external-actions-block" class="preview-block">
                                <div class="preview-block-label">Action Taken:</div>
                                <div id="preview-external-actions" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div id="preview-external-remarks-block" class="preview-block">
                                <div class="preview-block-label">Remarks:</div>
                                <div id="preview-external-remarks" class="preview-block-value preview-missing">—</div>
                            </div>
                            <div class="preview-block">
                                <div class="preview-block-label">Evidence / Attachments:</div>
                                <div id="preview-evidence-grid-external" class="preview-evidence-grid"></div>
                            </div>

                            <div class="preview-signatory-wrap">
                                <div>
                                    <div class="preview-signatory-label">Prepared by:</div>
                                    <?php if ($previewOfficerSignatureUrl !== ''): ?>
                                        <div class="preview-signature-wrap">
                                            <img src="<?= htmlspecialchars(
                                                $previewOfficerSignatureUrl,
                                            ) ?>" alt="Officer signature" class="preview-signature-img">
                                        </div>
                                    <?php endif; ?>
                                    <div id="preview-external-prepared-name" class="preview-signatory-name"><?= htmlspecialchars(
                                        $previewOfficerName,
                                    ) ?></div>
                                    <div class="preview-signatory-line">Detachment Commander</div>
                                    <div id="preview-external-prepared-line2" class="preview-signatory-line">SISCO-NCFL External Scty.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="modal fade preview-fullscreen-modal" id="previewFullscreenModal" tabindex="-1" aria-labelledby="previewFullscreenLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="previewFullscreenLabel">Full Report Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body preview-fullscreen-body">
                        <div id="preview-fullscreen-content" class="preview-fullscreen-sheet"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="quickAddModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
    <div class="modal modal--accent" style="max-width: 30rem;">
        <div class="modal-accent-header">
            <div>
                <h2 id="quick-add-title" class="modal-accent-title">Add New <span id="modalTypeName"></span></h2>
                <p class="modal-accent-subtitle">Create and select a new dropdown value</p>
            </div>
            <button type="button" class="modal-accent-close" aria-label="Close" onclick="closeQuickAddModal()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-accent-body">
            <div class="mb-3">
                <label class="form-label" for="newTypeName">Name</label>
                <input type="text" id="newTypeName" class="form-control" placeholder="e.g. Theft, Fire, etc.">
            </div>
            <input type="hidden" id="pendingType">
            <input type="hidden" id="pendingParentId">
            <input type="hidden" id="sourceSelectId">

            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="closeQuickAddModal()">Cancel</button>
                <button type="button" id="quick-add-save-btn" class="btn btn-primary" onclick="submitQuickAdd()">Save & Select</button>
            </div>
        </div>
    </div>
</div>

<div id="submitConfirmModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="submitConfirmModalLabel">
    <div class="modal modal--accent" style="max-width: 32rem;">
        <div class="modal-accent-header">
            <div>
                <h2 class="modal-accent-title" id="submitConfirmModalLabel">Final Confirmation</h2>
                <p class="modal-accent-subtitle">Review first before final submission</p>
            </div>
            <button type="button" class="modal-accent-close" aria-label="Close" onclick="closeSubmitConfirmModal()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-accent-body">
            <p class="mb-2">Are you sure you want to submit this report?</p>
            <p class="mb-0 text-muted">Please review the report details and preview first before final submission.</p>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="closeSubmitConfirmModal()">Review Again</button>
                <button type="button" id="confirm-submit-report" class="btn btn-primary">Yes, Submit Report</button>
            </div>
        </div>
    </div>
</div>

<div id="submitSuccessModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="submitSuccessModalLabel">
    <div class="modal modal--accent" style="max-width: 32rem;">
        <div class="modal-accent-header">
            <div>
                <h2 class="modal-accent-title" id="submitSuccessModalLabel">Report Submitted Successfully</h2>
                <p class="modal-accent-subtitle">A fresh submit form is now ready</p>
            </div>
            <button type="button" class="modal-accent-close" aria-label="Close" onclick="closeSubmitSuccessModal()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div class="modal-accent-body">
            <p class="mb-2">Your report has been sent to General Affairs Staff for review.</p>
            <p class="mb-0 text-muted">Report ID: <span class="font-mono"><?= htmlspecialchars(
                $successReportNo ?: '—',
            ) ?></span></p>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeSubmitSuccessModal()">Create New Report</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
'use strict';

const __submitReportDraftKey = <?= json_encode(
    'submit_report_draft_' . (string) ($currentUser['employee_no'] ?? 'security'),
) ?>;
const __submitReportSuccessNo = <?= json_encode((string) ($successReportNo ?? '')) ?>;
const __submitReportRequestMethod = <?= json_encode((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) ?>;

if (__submitReportSuccessNo !== '') {
    try {
        localStorage.removeItem(__submitReportDraftKey);
    } catch (e) {
        // ignore storage errors
    }
}

function submitFormBaseUrl() {
    return <?= json_encode(app_url('submit-report.php')) ?>;
}

function openAppModal(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;
    modalEl.classList.remove('hidden');
    modalEl.classList.add('active');
    document.body.classList.add('modal-open-frozen');
}

function closeAppModal(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;
    modalEl.classList.remove('active');
    modalEl.classList.add('hidden');

    const stillOpen = document.querySelector('.modal-overlay.active');
    if (!stillOpen) {
        document.body.classList.remove('modal-open-frozen');
        document.body.style.overflow = '';
    }
}

function closeQuickAddModal() {
    closeAppModal('quickAddModal');
}

function closeSubmitConfirmModal() {
    closeAppModal('submitConfirmModal');
}

function closeSubmitSuccessModal() {
    closeAppModal('submitSuccessModal');
}

function showQuickAddModal(type, parentId, sourceId) {
    if ((type === 'category' || type === 'sub_category') && (!parentId || String(parentId).trim() === '')) {
        const parentLabel = type === 'category' ? 'Subject' : 'Category';
        alert(`Please select ${parentLabel} first.`);
        return;
    }

    document.getElementById('modalTypeName').innerText = type.replace('_', ' ');
    document.getElementById('pendingType').value = type;
    document.getElementById('pendingParentId').value = parentId || '';
    document.getElementById('sourceSelectId').value = sourceId;
    document.getElementById('newTypeName').value = '';

    const inputEl = document.getElementById('newTypeName');
    openAppModal('quickAddModal');

    setTimeout(() => {
        if (inputEl) inputEl.focus();
    }, 80);
}

function fetchChildren(parentId, targetType, targetSelectId, selectedValue = null) {
    const targetSelect = document.getElementById(targetSelectId);
    if (!targetSelect) return;

    targetSelect.innerHTML = '<option value="">Loading...</option>';
    targetSelect.disabled = false;

    const url = `${submitFormBaseUrl()}?action=get_children&parent_id=${encodeURIComponent(parentId)}&target_type=${encodeURIComponent(targetType)}`;
    fetch(url, { credentials: 'same-origin' })
        .then(response => response.json())
        .then(data => {
            const label = targetType === 'sub_category' ? 'Subcategory' : 'Category';
            targetSelect.innerHTML = `<option value="" disabled selected>Select ${label}</option>`;

            (Array.isArray(data) ? data : []).forEach(item => {
                const opt = new Option(item.name, String(item.id));
                if (selectedValue !== null && String(selectedValue) === String(item.id)) {
                    opt.selected = true;
                }
                targetSelect.add(opt);
            });

            const addLabel = targetType === 'sub_category' ? 'Subcategory' : 'Category';
            targetSelect.add(new Option(`+ Add New ${addLabel}...`, 'add_new'));
            if (typeof window.__syncSubmitPreview === 'function') {
                window.__syncSubmitPreview();
            }
        });
}

function handleDropdownChange(selectElement, targetType, targetId) {
    const value = selectElement.value;

    if (value === 'add_new') {
        let parentId = null;
        if (selectElement.id === 'category_id') {
            parentId = document.getElementById('subject_id').value;
        } else if (selectElement.id === 'sub_category_id') {
            parentId = document.getElementById('category_id').value;
        }

        const type = selectElement.id === 'subject_id'
            ? 'subject'
            : (selectElement.id === 'category_id' ? 'category' : 'sub_category');
        showQuickAddModal(type, parentId, selectElement.id);
        selectElement.value = '';
        return;
    }

    if (selectElement.id === 'subject_id') {
        const sub = document.getElementById('sub_category_id');
        if (sub) {
            sub.innerHTML = '<option value="">Select Category First</option>';
            sub.disabled = true;
        }
    }

    if (targetId && value) {
        fetchChildren(value, targetType, targetId);
    }
}

function submitQuickAdd() {
    const name = document.getElementById('newTypeName').value.trim();
    const type = document.getElementById('pendingType').value;
    const parentId = document.getElementById('pendingParentId').value;
    const sourceId = document.getElementById('sourceSelectId').value;
    const saveBtn = document.getElementById('quick-add-save-btn');

    if (!name) {
        alert('Please enter a name');
        return;
    }

    if (saveBtn) saveBtn.setAttribute('disabled', 'disabled');

    const formData = new FormData();
    formData.append('action', 'quick_add');
    formData.append('csrf_token', <?= json_encode(csrf_token()) ?>);
    formData.append('name', name);
    formData.append('type', type);
    formData.append('parent_id', parentId);

    fetch(submitFormBaseUrl(), {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Failed to save value.');
            return;
        }

        const select = document.getElementById(sourceId);
        if (!select) return;

        const option = new Option(data.name, String(data.id), true, true);
        const addOption = Array.from(select.options).find(o => o.value === 'add_new');
        if (addOption) {
            select.add(option, addOption);
        } else {
            select.add(option);
        }

        select.dispatchEvent(new Event('change'));
        if (typeof window.__syncSubmitPreview === 'function') {
            window.__syncSubmitPreview();
        }
        closeQuickAddModal();
    })
    .catch(() => {
        alert('Unable to save value right now. Please try again.');
    })
    .finally(() => {
        if (saveBtn) saveBtn.removeAttribute('disabled');
    });
}

(function () {
    'use strict';

    const quickAddNameEl = document.getElementById('newTypeName');
    if (!quickAddNameEl) return;

    quickAddNameEl.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            submitQuickAdd();
        }
    });
}());

(function () {
    'use strict';

    document.addEventListener('click', (event) => {
        const overlay = event.target;
        if (!(overlay instanceof HTMLElement)) return;
        if (!overlay.classList.contains('modal-overlay')) return;
        if (overlay.id === 'quickAddModal') {
            closeQuickAddModal();
        } else if (overlay.id === 'submitConfirmModal') {
            closeSubmitConfirmModal();
        } else if (overlay.id === 'submitSuccessModal') {
            closeSubmitSuccessModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const quickAdd = document.getElementById('quickAddModal');
        const submitConfirm = document.getElementById('submitConfirmModal');
        if (quickAdd && quickAdd.classList.contains('active')) {
            closeQuickAddModal();
            return;
        }
        if (submitConfirm && submitConfirm.classList.contains('active')) {
            closeSubmitConfirmModal();
            return;
        }
        const submitSuccess = document.getElementById('submitSuccessModal');
        if (submitSuccess && submitSuccess.classList.contains('active')) {
            closeSubmitSuccessModal();
        }
    });
}());

(function () {
    'use strict';

    function showSuccessModalIfNeeded() {
        if (String(__submitReportSuccessNo || '').trim() === '') return;
        openAppModal('submitSuccessModal');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showSuccessModalIfNeeded);
    } else {
        setTimeout(showSuccessModalIfNeeded, 0);
    }

    window.addEventListener('pageshow', () => {
        setTimeout(showSuccessModalIfNeeded, 0);
    });
}());

(function () {
    'use strict';

    const submitFormEl = document.getElementById('submit-form');
    if (!submitFormEl) return;

    const isAfterSuccessfulSubmit = String(__submitReportSuccessNo || '').trim() !== '';

    const fieldIds = [
        'report_type',
        'entity',
        'subject_id',
        'category_id',
        'sub_category_id',
        'location',
        'severity',
        'department',
        'details',
        'assessment',
        'recommendations',
        'actions-taken',
        'remarks',
    ];

    function readDraft() {
        try {
            const raw = localStorage.getItem(__submitReportDraftKey);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function writeDraft() {
        const payload = { values: {}, ts: Date.now() };
        fieldIds.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            payload.values[id] = String(el.value ?? '');
        });

        try {
            localStorage.setItem(__submitReportDraftKey, JSON.stringify(payload));
        } catch (e) {
            // ignore storage quota/private mode errors
        }
    }

    function restoreDraft() {
        if (isAfterSuccessfulSubmit) return;

        const draft = readDraft();
        if (!draft || !draft.values) return;

        // If this is a POST response (validation error round-trip), prefer server-rendered values.
        if (String(__submitReportRequestMethod).toUpperCase() === 'POST') return;

        const subjectEl = document.getElementById('subject_id');
        const categoryEl = document.getElementById('category_id');
        const subCategoryEl = document.getElementById('sub_category_id');

        fieldIds.forEach((id) => {
            if (id === 'category_id' || id === 'sub_category_id') return;
            const el = document.getElementById(id);
            if (!el) return;
            const value = String(draft.values[id] ?? '');
            if (value !== '') {
                el.value = value;
            }
        });

        const subjectVal = String(draft.values['subject_id'] ?? '');
        const categoryVal = String(draft.values['category_id'] ?? '');
        const subCategoryVal = String(draft.values['sub_category_id'] ?? '');

        if (subjectEl && subjectVal !== '') {
            subjectEl.value = subjectVal;
            subjectEl.dataset.selected = subjectVal;
            if (categoryEl) categoryEl.dataset.selected = categoryVal;
            if (subCategoryEl) subCategoryEl.dataset.selected = subCategoryVal;

            if (typeof fetchChildren === 'function') {
                fetchChildren(subjectVal, 'category', 'category_id', categoryVal || null);
                if (categoryVal) {
                    fetchChildren(categoryVal, 'sub_category', 'sub_category_id', subCategoryVal || null);
                }
            }
        }
    }

    let draftSaveTimer = null;
    function queueDraftSave() {
        if (draftSaveTimer) {
            clearTimeout(draftSaveTimer);
        }
        draftSaveTimer = setTimeout(writeDraft, 220);
    }

    function clearForFreshReport() {
        submitFormEl.reset();

        const clearIds = [
            'location',
            'details',
            'assessment',
            'recommendations',
            'actions-taken',
            'remarks',
        ];

        clearIds.forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = '';
            }
        });

        const categoryEl = document.getElementById('category_id');
        if (categoryEl) {
            categoryEl.innerHTML = '<option value="" disabled selected>Select Subject First</option>';
            categoryEl.disabled = true;
            categoryEl.dataset.selected = '';
        }

        const subCategoryEl = document.getElementById('sub_category_id');
        if (subCategoryEl) {
            subCategoryEl.innerHTML = '<option value="" disabled selected>Select Category First</option>';
            subCategoryEl.disabled = true;
            subCategoryEl.dataset.selected = '';
        }

        const evidenceEl = document.getElementById('evidence');
        if (evidenceEl) {
            evidenceEl.value = '';
        }

        if (typeof window.renderEvidencePreview === 'function') {
            window.renderEvidencePreview();
        }
        if (typeof window.__syncSubmitPreview === 'function') {
            window.__syncSubmitPreview();
        }
    }

    restoreDraft();

    if (isAfterSuccessfulSubmit) {
        clearForFreshReport();
    }

    fieldIds.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', queueDraftSave);
        el.addEventListener('change', queueDraftSave);
    });

    submitFormEl.addEventListener('submit', writeDraft);
}());

(function () {
    'use strict';

    const submitFormEl = document.getElementById('submit-form');
    const submitReportBtn = document.getElementById('submit-report-btn');
    const submitConfirmModalEl = document.getElementById('submitConfirmModal');
    const confirmSubmitReportBtn = document.getElementById('confirm-submit-report');
    if (!submitFormEl || !submitConfirmModalEl || !confirmSubmitReportBtn) return;

    function openConfirmPrompt() {
        if (!submitFormEl.reportValidity()) {
            return;
        }
        openAppModal('submitConfirmModal');
    }

    if (submitReportBtn) {
        submitReportBtn.addEventListener('click', openConfirmPrompt);
    }

    submitFormEl.addEventListener('submit', (event) => {
        if (submitFormEl.dataset.finalSubmitConfirmed === '1') {
            submitFormEl.dataset.finalSubmitConfirmed = '';
            return;
        }

        event.preventDefault();
        openConfirmPrompt();
    });

    confirmSubmitReportBtn.addEventListener('click', () => {
        if (!submitFormEl.reportValidity()) {
            return;
        }

        confirmSubmitReportBtn.setAttribute('disabled', 'disabled');
        confirmSubmitReportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Submitting...';
        if (submitReportBtn) {
            submitReportBtn.setAttribute('disabled', 'disabled');
        }

        submitFormEl.dataset.finalSubmitConfirmed = '1';
        closeSubmitConfirmModal();

        if (submitFormEl.requestSubmit) {
            submitFormEl.requestSubmit();
        } else {
            submitFormEl.submit();
        }
    });
}());

(function () {
    'use strict';

    const openFullscreenPreviewBtn = document.getElementById('open-fullscreen-preview');
    const reportTypeEl = document.getElementById('report_type');
    const entityEl = document.getElementById('entity');
    const subjectEl = document.getElementById('subject_id');
    const categoryEl = document.getElementById('category_id');
    const subCategoryEl = document.getElementById('sub_category_id');
    const departmentEl = document.getElementById('department');
    const locationEl = document.getElementById('location');
    const severityEl = document.getElementById('severity');
    const detailsEl = document.getElementById('details');
    const assessmentEl = document.getElementById('assessment');
    const recommendationsEl = document.getElementById('recommendations');
    const actionsEl = document.getElementById('actions-taken');
    const remarksEl = document.getElementById('remarks');
    const evidenceEl = document.getElementById('evidence');
    const csrfTokenEl = document.querySelector('input[name="csrf_token"]');

    const previewSeedUrl = <?= json_encode(app_url('api/report_pdf_preview_seed.php')) ?>;
    const previewPdfBaseUrl = <?= json_encode(app_url('api/report_pdf.php')) ?>;

    if (!openFullscreenPreviewBtn) return;

    function selectedText(selectEl, fallback = '') {
        if (!selectEl || !selectEl.options || selectEl.selectedIndex < 0) return fallback;
        const value = String(selectEl.value || '').trim();
        if (!value || value === 'add_new') return fallback;
        const text = String(selectEl.options[selectEl.selectedIndex]?.text || '').trim();
        return text || fallback;
    }

    function setLoadingState(isLoading) {
        openFullscreenPreviewBtn.disabled = isLoading;
        if (isLoading) {
            openFullscreenPreviewBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        } else {
            openFullscreenPreviewBtn.innerHTML = '<i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>';
        }
    }

    async function openPdfPreviewInNewTab() {
        if (typeof window.__syncSubmitPreview === 'function') {
            window.__syncSubmitPreview();
        }

        const popup = window.open('', '_blank');
        if (!popup) {
            alert('Popup was blocked. Please allow popups to open PDF preview.');
            return;
        }

        setLoadingState(true);
        try {
            const payload = new FormData();
            payload.append('csrf_token', String(csrfTokenEl?.value || ''));
            payload.append('security_type', String(reportTypeEl?.value || 'internal'));
            payload.append('building', String(entityEl?.value || 'NCFL'));
            payload.append('subject_text', selectedText(subjectEl, 'REPORT'));
            payload.append('category_text', selectedText(categoryEl, 'REPORT'));
            payload.append('sub_category_text', selectedText(subCategoryEl, 'N/A'));
            payload.append('department_text', selectedText(departmentEl, 'DEPARTMENT'));
            payload.append('location', String(locationEl?.value || ''));
            payload.append('severity', String(severityEl?.value || 'medium'));
            payload.append('details', String(detailsEl?.value || ''));
            payload.append('assessment', String(assessmentEl?.value || ''));
            payload.append('recommendations', String(recommendationsEl?.value || ''));
            payload.append('actions_taken', String(actionsEl?.value || ''));
            payload.append('remarks', String(remarksEl?.value || ''));

            if (evidenceEl && evidenceEl.files && evidenceEl.files.length) {
                Array.from(evidenceEl.files).forEach(file => {
                    payload.append('evidence[]', file);
                });
            }

            const res = await fetch(previewSeedUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: payload,
            });

            let data = null;
            try {
                data = await res.json();
            } catch (e) {
                data = null;
            }

            if (!res.ok || !data || !data.success || !data.token) {
                popup.close();
                alert((data && data.message) ? data.message : 'Unable to generate PDF preview.');
                return;
            }

            const pdfUrl = `${previewPdfBaseUrl}?preview_token=${encodeURIComponent(String(data.token))}&preview=1`;
            popup.location.href = pdfUrl;
        } catch (error) {
            popup.close();
            alert('Unable to generate PDF preview right now.');
        } finally {
            setLoadingState(false);
        }
    }

    openFullscreenPreviewBtn.title = 'Open PDF preview in new tab';
    openFullscreenPreviewBtn.setAttribute('aria-label', 'Open PDF preview in new tab');
    openFullscreenPreviewBtn.addEventListener('click', openPdfPreviewInNewTab);
}());

(function () {
    'use strict';

    const reportTypeEl = document.getElementById('report_type');
    const entityEl = document.getElementById('entity');
    const subjectEl = document.getElementById('subject_id');
    const categoryEl = document.getElementById('category_id');
    const subCategoryEl = document.getElementById('sub_category_id');
    const locationEl = document.getElementById('location');
    const severityEl = document.getElementById('severity');
    const departmentEl = document.getElementById('department');
    const detailsEl = document.getElementById('details');
    const assessmentEl = document.getElementById('assessment');
    const recommendationsEl = document.getElementById('recommendations');
    const actionsEl = document.getElementById('actions-taken');
    const remarksEl = document.getElementById('remarks');

    const templateInternal = document.getElementById('preview-template-internal');
    const templateExternal = document.getElementById('preview-template-external');

    const internalFor = document.getElementById('preview-internal-for');
    const internalThru = document.getElementById('preview-internal-thru');
    const internalSubject = document.getElementById('preview-internal-subject');
    const internalDetails = document.getElementById('preview-internal-details');
    const internalAssessment = document.getElementById('preview-internal-assessment');
    const internalRecommendations = document.getElementById('preview-internal-recommendations');
    const internalActionsBlock = document.getElementById('preview-internal-actions-block');
    const internalActions = document.getElementById('preview-internal-actions');
    const internalRemarksBlock = document.getElementById('preview-internal-remarks-block');
    const internalRemarks = document.getElementById('preview-internal-remarks');

    const externalTo = document.getElementById('preview-external-to');
    const externalThru = document.getElementById('preview-external-thru');
    const externalSubject = document.getElementById('preview-external-subject');
    const externalDetails = document.getElementById('preview-external-details');
    const externalAssessment = document.getElementById('preview-external-assessment');
    const externalRecommendations = document.getElementById('preview-external-recommendations');
    const externalActionsBlock = document.getElementById('preview-external-actions-block');
    const externalActions = document.getElementById('preview-external-actions');
    const externalRemarksBlock = document.getElementById('preview-external-remarks-block');
    const externalRemarks = document.getElementById('preview-external-remarks');
    const internalPreparedLine1 = document.getElementById('preview-internal-prepared-line1');
    const externalPreparedLine2 = document.getElementById('preview-external-prepared-line2');

    function selectedText(selectEl, fallback = '—') {
        if (!selectEl || !selectEl.options || selectEl.selectedIndex < 0) return fallback;
        const value = String(selectEl.value || '').trim();
        if (!value || value === 'add_new') return fallback;
        const text = String(selectEl.options[selectEl.selectedIndex]?.text || '').trim();
        return text || fallback;
    }

    function normalizeBlock(value) {
        const txt = String(value || '').trim();
        return txt === '' ? '—' : txt;
    }

    function applyMissingClass(el) {
        if (!el) return;
        const isMissing = String(el.textContent || '').trim() === '—';
        el.classList.toggle('preview-missing', isMissing);
    }

    function toggleBlock(el, show) {
        if (!el) return;
        el.style.display = show ? '' : 'none';
    }

    function buildSubject() {
        const subject = selectedText(subjectEl, 'Subject');
        const category = selectedText(categoryEl, 'Category');
        const subCategory = selectedText(subCategoryEl, 'Sub-Category');
        return `${subject}: ${category} - ${subCategory}`;
    }

    function syncPreview() {
        const selectedType = String(reportTypeEl?.value || '').toLowerCase();
        const type = selectedType === 'external' ? 'external' : 'internal';
        const isInternal = type === 'internal';

        if (templateInternal && templateExternal) {
            templateInternal.classList.toggle('d-none', !isInternal);
            templateExternal.classList.toggle('d-none', isInternal);
        }

        const subjectText = buildSubject();
        const departmentText = selectedText(departmentEl, 'DEPARTMENT').toUpperCase();
        const departmentPicText = `${departmentText} PIC`;
        const detailsRaw = String(detailsEl?.value || '').trim();
        const assessmentRaw = String(assessmentEl?.value || '').trim();
        const recommendationsRaw = String(recommendationsEl?.value || '').trim();
        const actionsRaw = String(actionsEl?.value || '').trim();
        const remarksRaw = String(remarksEl?.value || '').trim();

        const detailsText = normalizeBlock(detailsRaw);
        const assessmentText = normalizeBlock(assessmentRaw);
        const recommendationsText = normalizeBlock(recommendationsRaw);
        const actionsText = normalizeBlock(actionsRaw);
        const remarksText = normalizeBlock(remarksRaw);

        if (internalFor) internalFor.textContent = departmentPicText;
        if (externalTo) externalTo.textContent = departmentPicText;
        if (internalThru) internalThru.textContent = 'GA MANAGER';
        if (externalThru) externalThru.textContent = 'GA MANAGER';

        if (internalSubject) internalSubject.textContent = subjectText;
        if (externalSubject) externalSubject.textContent = subjectText;

        if (internalDetails) internalDetails.textContent = detailsText;
        if (externalDetails) externalDetails.textContent = detailsText;
        if (internalAssessment) internalAssessment.textContent = assessmentText;
        if (externalAssessment) externalAssessment.textContent = assessmentText;
        if (internalRecommendations) internalRecommendations.textContent = recommendationsText;
        if (externalRecommendations) externalRecommendations.textContent = recommendationsText;
        if (internalActions) internalActions.textContent = actionsText;
        if (externalActions) externalActions.textContent = actionsText;
        if (internalRemarks) internalRemarks.textContent = remarksText;
        if (externalRemarks) externalRemarks.textContent = remarksText;

        toggleBlock(internalActionsBlock, actionsRaw !== '');
        toggleBlock(externalActionsBlock, actionsRaw !== '');
        toggleBlock(internalRemarksBlock, remarksRaw !== '');
        toggleBlock(externalRemarksBlock, remarksRaw !== '');

        if (internalPreparedLine1) {
            const entity = selectedText(entityEl, 'NCFL');
            internalPreparedLine1.textContent = `${entity} / Security Officer`;
        }
        if (externalPreparedLine2) {
            const entity = selectedText(entityEl, 'NCFL').toUpperCase();
            externalPreparedLine2.textContent = `SISCO-${entity} External Scty.`;
        }

        [
            internalDetails,
            externalDetails,
            internalAssessment,
            externalAssessment,
            internalRecommendations,
            externalRecommendations,
            internalActions,
            externalActions,
            internalRemarks,
            externalRemarks,
        ].forEach(applyMissingClass);
    }

    [reportTypeEl, entityEl, subjectEl, categoryEl, subCategoryEl, locationEl, severityEl, departmentEl, detailsEl, assessmentEl, recommendationsEl, actionsEl, remarksEl]
        .filter(Boolean)
        .forEach(el => {
            el.addEventListener('change', syncPreview);
            el.addEventListener('input', syncPreview);
        });

    window.__syncSubmitPreview = syncPreview;
    syncPreview();
}());

(function () {
    'use strict';

    const input    = document.getElementById('evidence');
    const dropzone = document.getElementById('evidence-dropzone');
    const grid     = document.getElementById('sr-preview-grid');
    const counter  = document.getElementById('sr-file-count');
    const previewEvidenceGridInternal = document.getElementById('preview-evidence-grid-internal');
    const previewEvidenceGridExternal = document.getElementById('preview-evidence-grid-external');
    const subjectEl = document.getElementById('subject_id');
    const categoryEl = document.getElementById('category_id');
    const subCategoryEl = document.getElementById('sub_category_id');

    if (subjectEl && categoryEl && subCategoryEl) {
        const selectedSubject = subjectEl.dataset.selected || '';
        const selectedCategory = categoryEl.dataset.selected || '';
        const selectedSubCategory = subCategoryEl.dataset.selected || '';

        if (selectedSubject) {
            fetchChildren(selectedSubject, 'category', 'category_id', selectedCategory);
            if (selectedCategory) {
                fetchChildren(selectedCategory, 'sub_category', 'sub_category_id', selectedSubCategory);
            }
        }
    }

    if (!input || !dropzone || !grid || !counter) return;

    let fileList = [];

    /** Push the fileList back into the real <input> */
    function syncInput() {
        try {
            const dt = new DataTransfer();
            fileList.forEach(f => dt.items.add(f));
            input.files = dt.files;
        } catch (e) { /* Safari fallback — grid is still visual-only */ }
    }

    /** Rebuild the preview grid from fileList */
    function renderGrid() {
        grid.innerHTML = '';

        if (!fileList.length) {
            grid.style.display = 'none';
            counter.textContent = '';
            if (previewEvidenceGridInternal) previewEvidenceGridInternal.innerHTML = '';
            if (previewEvidenceGridExternal) previewEvidenceGridExternal.innerHTML = '';
            return;
        }

        grid.style.display = 'grid';
        counter.textContent = fileList.length === 1
            ? '1 image selected'
            : fileList.length + ' images selected';

        if (previewEvidenceGridInternal) previewEvidenceGridInternal.innerHTML = '';
        if (previewEvidenceGridExternal) previewEvidenceGridExternal.innerHTML = '';

        const appendPreviewEvidence = (container, file) => {
            if (!container) return;
            const item = document.createElement('div');
            item.className = 'preview-evidence-item';

            const img = document.createElement('img');
            img.alt = file.name;
            const url = URL.createObjectURL(file);
            img.src = url;
            img.onload = () => URL.revokeObjectURL(url);

            item.appendChild(img);
            container.appendChild(item);
        };

        fileList.forEach((file, idx) => {
            const item = document.createElement('div');
            item.className = 'sr-preview-item';

            const img = document.createElement('img');
            img.alt = file.name;
            const url = URL.createObjectURL(file);
            img.src = url;
            img.onload = () => URL.revokeObjectURL(url);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sr-preview-remove';
            btn.title = 'Remove ' + file.name;
            btn.innerHTML = '<i class="bi bi-x"></i>';
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileList.splice(idx, 1);
                syncInput();
                renderGrid();
            });

            item.appendChild(img);
            item.appendChild(btn);
            grid.appendChild(item);

            appendPreviewEvidence(previewEvidenceGridInternal, file);
            appendPreviewEvidence(previewEvidenceGridExternal, file);
        });
    }

    /** Add new files, skip duplicates by name+size */
    function addFiles(newFiles) {
        newFiles.forEach(f => {
            const dup = fileList.some(x => x.name === f.name && x.size === f.size);
            if (!dup && (f.type === 'image/png' || f.type === 'image/jpeg')) {
                fileList.push(f);
            }
        });
        syncInput();
        renderGrid();
    }

    /* Click on zone → open file picker */
    dropzone.addEventListener('click', () => input.click());

    /* File picker change */
    input.addEventListener('change', () => {
        if (input.files && input.files.length) {
            addFiles(Array.from(input.files));
        }
    });

    /* Drag and drop */
    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.add('drag-over');
        });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.remove('drag-over');
        });
    });
    dropzone.addEventListener('drop', e => {
        const files = e.dataTransfer && e.dataTransfer.files
            ? Array.from(e.dataTransfer.files) : [];
        if (files.length) addFiles(files);
    });
}());
</script>
