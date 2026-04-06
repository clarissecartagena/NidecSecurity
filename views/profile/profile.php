<?php
/**
 * My Profile page
 * - Users can update their email address
 * - Users can upload a signature image (one-time, permanent)
 */
$roleLabels = [
    'security' => 'Security Officer',
    'ga_staff' => 'General Affairs Staff',
    'ga_manager' => 'General Affairs Manager',
    'department' => 'Department',
];
$userRoleLabel = $roleLabels[$dbUser['role'] ?? ''] ?? ucfirst(str_replace('_', ' ', $dbUser['role'] ?? ''));
$hasSignature = !empty($dbUser['signature_path']);
$signatureUrl = $hasSignature ? htmlspecialchars(app_url($dbUser['signature_path'])) : '';
?>

<main class="main-content">
    <div class="animate-fade-in">

        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1">
                <i class="bi bi-person-circle me-2 text-primary"></i>My Profile
            </h1>
            <p class="text-sm text-muted-foreground mb-0">
                Manage your account information and signature.
            </p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- ── Account Info Card ───────────────────────────────────────── -->
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person me-2"></i>Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars(app_url('profile.php')); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
                                csrf_token(),
                            ); ?>" />
                            <input type="hidden" name="action" value="update_email" />

                            <div class="mb-3">
                                <label class="form-label fw-medium">Full Name</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($dbUser['name'] ?? ''); ?>"
                                       disabled readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Username</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($dbUser['username'] ?? ''); ?>"
                                       disabled readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Role</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($userRoleLabel); ?>"
                                       disabled readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" for="profile-email">
                                    Email Address
                                    <span id="profile-email-verified" class="ms-2 text-success small d-none">
                                        <i class="bi bi-check-circle-fill me-1"></i>Verified
                                    </span>
                                </label>
                                <input type="email" id="profile-email" name="email"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($dbUser['email'] ?? ''); ?>"
                                       placeholder="your@email.com"
                                       required />
                                <div id="profile-email-helper" class="form-text"></div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Signature Card ──────────────────────────────────────────── -->
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pen me-2"></i>Signature
                        </h5>
                    </div>
                    <div class="card-body">

                        <?php if ($hasSignature): ?>
                            <!-- Signature already uploaded — read-only -->
                            <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                                <div>
                                    Your signature has been uploaded. It will appear on PDF reports when you pass a report to the next person.
                                    <strong>Signatures cannot be changed once set.</strong>
                                </div>
                            </div>
                            <div class="border rounded p-3 bg-body-tertiary d-flex flex-column align-items-center gap-2"
                                 style="min-height:120px;">
                                <p class="text-xs text-muted-foreground mb-1">Preview — how it looks above your name:</p>
                                <img src="<?php echo $signatureUrl; ?>"
                                     alt="Your signature"
                                     style="max-height:80px; max-width:220px; object-fit:contain;"
                                     class="mb-1" />
                                <span class="fw-semibold text-foreground" style="font-size:0.9rem; letter-spacing:0.05em;">
                                    <?php echo htmlspecialchars(strtoupper($dbUser['name'] ?? '')); ?>
                                </span>
                            </div>

                        <?php else: ?>
                            <!-- No signature yet — show upload button -->
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                                <div>
                                    You have not uploaded a signature yet. Your signature will appear above your name on PDF reports
                                    once you have forwarded or approved a report.
                                    <strong>This is a one-time upload and cannot be changed.</strong>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-outline-primary"
                                        onclick="ProfilePage.openSignatureModal()">
                                    <i class="bi bi-upload me-2"></i>Upload Signature
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div><!-- /row -->
    </div>
</main>

<!-- Cropper.js — loaded only on the profile page where signature upload is available -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js" crossorigin="anonymous"></script>

<!-- ── Signature Upload Modal ──────────────────────────────────────────────── -->
<div id="signature-modal" class="modal-overlay hidden" role="dialog" aria-modal="true"
     aria-labelledby="sig-modal-title">
    <div class="modal modal--accent" style="max-width:600px;">

        <div class="modal-accent-header">
            <div>
                <h2 id="sig-modal-title" class="modal-accent-title">
                    <i class="bi bi-pen me-2" aria-hidden="true"></i>Upload Signature
                </h2>
                <p class="modal-accent-subtitle">One-time upload — cannot be changed after confirmation</p>
            </div>
            <button type="button" class="modal-accent-close" aria-label="Close"
                    onclick="ProfilePage.closeSignatureModal()">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="modal-accent-body">

            <!-- Instructions -->
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                <div>
                    <strong>Important — please read before uploading:</strong>
                    <ul class="mb-0 mt-1 ps-3">
                        <li>Accepted formats: PNG, JPG, GIF, WebP (max 10 MB).</li>
                        <li>Use the <strong>crop tool</strong> below to remove empty space around your signature.</li>
                        <li><strong>This upload is final and cannot be changed once confirmed.</strong></li>
                    </ul>
                </div>
            </div>

            <!-- File picker -->
            <div class="mb-3">
                <label class="form-label fw-medium" for="sig-file-input">
                    Select signature image
                </label>
                <input type="file" id="sig-file-input" class="form-control"
                       accept="image/png,image/jpeg,image/gif,image/webp"
                       onchange="ProfilePage.onFileSelected(this)" />
            </div>

            <!-- ── Crop area — shown after file selected ── -->
            <div id="sig-crop-wrap" class="hidden mb-3">
                <p class="text-sm text-muted-foreground mb-2">
                    <strong>Step 1 — Crop your signature:</strong>
                    Drag the box to frame only the signature strokes, then click <em>Apply Crop</em>.
                    Use <em>Skip — Use Original</em> to upload without cropping.
                </p>
                <div style="max-height:280px; overflow:hidden; background:#e9ecef; border:1px solid #dee2e6; border-radius:0.375rem;">
                    <img id="sig-crop-img" src="" alt="Crop target" style="display:block; max-width:100%;" />
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-primary"
                            onclick="ProfilePage.applyCrop()">
                        <i class="bi bi-crop me-1"></i>Apply Crop
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="ProfilePage.skipCrop()">
                        Skip — Use Original
                    </button>
                </div>
            </div>

            <!-- Preview card — shown after crop applied or skipped -->
            <div id="sig-preview-wrap" class="hidden mb-3">
                <p class="text-sm text-muted-foreground mb-2">
                    <strong>Step 2 — Preview</strong> — how your signature will look on the report:
                </p>
                <div class="border rounded p-3 bg-body-tertiary d-flex flex-column align-items-center gap-1"
                     style="min-height:110px;">
                    <img id="sig-preview-img" src="" alt="Signature preview"
                         style="max-height:80px; max-width:280px; object-fit:contain;" />
                    <span class="fw-semibold text-foreground mt-1" style="font-size:0.9rem; letter-spacing:0.05em;">
                        <?php echo htmlspecialchars(strtoupper($dbUser['name'] ?? '')); ?>
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-link mt-1 p-0"
                        onclick="ProfilePage.reopenCrop()">
                    <i class="bi bi-arrow-left-circle me-1"></i>Re-crop
                </button>
            </div>

            <!-- Confirmation checkbox — shown after preview is loaded -->
            <div id="sig-confirm-wrap" class="hidden mb-3">
                <div class="form-check d-flex align-items-center gap-2">
                    <input type="checkbox" id="sig-confirm-check"
                           class="form-check-input flex-shrink-0"
                           style="width:1.25rem; height:1.25rem; cursor:pointer; margin-top:0;"
                           onchange="ProfilePage.toggleUploadBtn()" />
                    <label class="form-check-label" for="sig-confirm-check" style="cursor:pointer;">
                        I understand that this signature <strong class="text-danger">cannot be changed</strong> once uploaded.
                    </label>
                </div>
            </div>

            <!-- Footer actions -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="ProfilePage.closeSignatureModal()">Cancel</button>
                <button type="button" id="sig-upload-btn" class="btn btn-primary" disabled
                        onclick="ProfilePage.submitSignature()">
                    <i class="bi bi-check-circle me-1"></i>Confirm &amp; Upload
                </button>
            </div>

        </div>
    </div>
</div>

<script>
const ProfilePage = {
    _cropper: null,      // active Cropper.js instance
    _croppedBlob: null,  // blob produced by applyCrop(); null = use original file

    initEmailStatus() {
        const emailInput = document.getElementById('profile-email');
        if (!emailInput) return;
        emailInput.addEventListener('input', () => this.updateEmailStatus());
        emailInput.addEventListener('blur', () => this.updateEmailStatus());
        this.updateEmailStatus();
    },

    updateEmailStatus() {
        const emailInput = document.getElementById('profile-email');
        const helper = document.getElementById('profile-email-helper');
        const verified = document.getElementById('profile-email-verified');
        if (!emailInput || !helper || !verified) return;

        const email = String(emailInput.value || '').trim();

        if (email === '') {
            helper.textContent = 'Email is required for notification purposes. Please enter your email.';
            helper.classList.remove('text-success', 'text-warning');
            helper.classList.add('text-danger');
            verified.classList.add('d-none');
            return;
        }

        if (emailInput.checkValidity()) {
            helper.textContent = 'Email is ready and will be used for notifications.';
            helper.classList.remove('text-danger', 'text-warning');
            helper.classList.add('text-success');
            verified.classList.remove('d-none');
            return;
        }

        helper.textContent = 'Please enter a valid email address for notification purposes.';
        helper.classList.remove('text-success', 'text-danger');
        helper.classList.add('text-warning');
        verified.classList.add('d-none');
    },

    openSignatureModal() {
        document.getElementById('sig-file-input').value = '';
        this._hideCropAndPreview();
        this._destroyCropper();
        this._croppedBlob = null;
        const overlay = document.getElementById('signature-modal');
        overlay.classList.remove('hidden');
        overlay.classList.add('active');
    },

    closeSignatureModal() {
        this._destroyCropper();
        this._croppedBlob = null;
        const overlay = document.getElementById('signature-modal');
        overlay.classList.remove('active');
        overlay.classList.add('hidden');
    },

    // ── File selected ────────────────────────────────────────────────────────
    onFileSelected(input) {
        const file = input.files[0];
        this._hideCropAndPreview();
        this._destroyCropper();
        this._croppedBlob = null;

        if (!file) return;

        const validTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Invalid file type. Please select a PNG, JPG, GIF, or WebP image.');
            input.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('File is too large. Maximum size is 10 MB.');
            input.value = '';
            return;
        }

        // Show the crop area and initialise Cropper.js
        const cropImg = document.getElementById('sig-crop-img');
        const reader  = new FileReader();
        reader.onload = (e) => {
            cropImg.src = e.target.result;
            document.getElementById('sig-crop-wrap').classList.remove('hidden');

            // Initialise Cropper after the image loads in the DOM
            cropImg.onload = () => {
                this._destroyCropper();
                this._cropper = new Cropper(cropImg, {
                    viewMode: 1,
                    autoCropArea: 0.9,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    rotatable: false,
                });
            };
        };
        reader.readAsDataURL(file);
    },

    // ── Apply the current crop box ───────────────────────────────────────────
    applyCrop() {
        if (!this._cropper) return;
        const canvas = this._cropper.getCroppedCanvas({ imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
        if (!canvas) {
            alert('Could not crop the image. Please try again or use "Skip — Use Original".');
            return;
        }

        canvas.toBlob((blob) => {
            if (!blob) {
                alert('Could not process the cropped image. Please try "Skip — Use Original".');
                return;
            }
            this._croppedBlob = blob;
            this._showPreview(canvas.toDataURL('image/png'));
        }, 'image/png');
    },

    // ── Skip cropping — use the file as-is ───────────────────────────────────
    skipCrop() {
        const fileInput = document.getElementById('sig-file-input');
        const file = fileInput.files[0];
        if (!file) return;

        this._croppedBlob = null; // will upload original file
        const reader = new FileReader();
        reader.onload = (e) => this._showPreview(e.target.result);
        reader.readAsDataURL(file);
    },

    // ── Re-open crop after preview ───────────────────────────────────────────
    reopenCrop() {
        document.getElementById('sig-preview-wrap').classList.add('hidden');
        document.getElementById('sig-confirm-wrap').classList.add('hidden');
        document.getElementById('sig-confirm-check').checked = false;
        document.getElementById('sig-upload-btn').disabled = true;
        this._croppedBlob = null;

        // Re-show crop area and re-initialise the cropper (it was destroyed in _showPreview)
        const cropImg = document.getElementById('sig-crop-img');
        document.getElementById('sig-crop-wrap').classList.remove('hidden');
        this._destroyCropper();
        this._cropper = new Cropper(cropImg, {
            viewMode: 1,
            autoCropArea: 0.9,
            movable: true,
            zoomable: true,
            scalable: false,
            rotatable: false,
        });
    },

    // ── Show preview after crop/skip ─────────────────────────────────────────
    _showPreview(dataUrl) {
        this._destroyCropper();
        document.getElementById('sig-crop-wrap').classList.add('hidden');
        document.getElementById('sig-preview-img').src = dataUrl;
        document.getElementById('sig-preview-wrap').classList.remove('hidden');
        document.getElementById('sig-confirm-wrap').classList.remove('hidden');
        document.getElementById('sig-confirm-check').checked = false;
        document.getElementById('sig-upload-btn').disabled = true;
    },

    // ── Hide both crop and preview areas ────────────────────────────────────
    _hideCropAndPreview() {
        document.getElementById('sig-crop-wrap').classList.add('hidden');
        document.getElementById('sig-preview-wrap').classList.add('hidden');
        document.getElementById('sig-confirm-wrap').classList.add('hidden');
        document.getElementById('sig-confirm-check').checked = false;
        document.getElementById('sig-upload-btn').disabled = true;
    },

    // ── Destroy any active Cropper.js instance ───────────────────────────────
    _destroyCropper() {
        if (this._cropper) {
            this._cropper.destroy();
            this._cropper = null;
        }
    },

    toggleUploadBtn() {
        const checked = document.getElementById('sig-confirm-check').checked;
        const hasFile = document.getElementById('sig-file-input').files.length > 0;
        document.getElementById('sig-upload-btn').disabled = !(checked && hasFile);
    },

    async submitSignature() {
        const fileInput = document.getElementById('sig-file-input');
        const file = fileInput.files[0];
        if (!file) return;

        const btn = document.getElementById('sig-upload-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Uploading…';

        const formData = new FormData();
        // Use the cropped blob (PNG) if the user cropped, otherwise the original file
        if (this._croppedBlob) {
            formData.append('signature', this._croppedBlob, 'signature_cropped.png');
        } else {
            formData.append('signature', file);
        }
        formData.append('csrf_token', <?php echo json_encode(csrf_token()); ?>);

        try {
            const res = await fetch(<?php echo json_encode(app_url('api/upload_signature.php')); ?>, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (json.success) {
                window.location.reload();
            } else {
                alert('Upload failed: ' + (json.error || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm & Upload';
            }
        } catch (err) {
            alert('Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm & Upload';
        }
    },
};

document.addEventListener('DOMContentLoaded', () => {
    ProfilePage.initEmailStatus();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
