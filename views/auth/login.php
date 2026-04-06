<?php
$pageTitle = $pageTitle ?? 'Login';
$currentPage = $currentPage ?? 'login.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    /* Premium card shadow (soft, layered) */
    .login-card-elevated {
        box-shadow:
            0 1px 1px rgba(0, 0, 0, 0.10),
            0 12px 26px rgba(0, 0, 0, 0.22),
            0 28px 64px rgba(0, 0, 0, 0.28) !important;
        transition: box-shadow 200ms ease, transform 200ms ease;
    }

    .login-card-elevated:hover {
        box-shadow:
            0 1px 1px rgba(0, 0, 0, 0.10),
            0 16px 34px rgba(0, 0, 0, 0.26),
            0 34px 78px rgba(0, 0, 0, 0.30) !important;
        transform: translateY(-1px);
    }

    @media (prefers-reduced-motion: reduce) {
        .login-card-elevated {
            transition: none !important;
        }
        .login-card-elevated:hover {
            transform: none;
        }
    }

    /* Increase Label Font Size */
    .custom-label {
        margin-left: 5.5%;
        color: #333;
    }

    /* 1. SEAMLESS INPUT: Remove the middle line */
    .input-group-text {
        background-color: #ffffff !important;
        border-right: none !important; 
        color: #6c757d; 
        padding-right: 5px; 
    }
    
    .form-control {
        background-color: #ffffff !important;
        border-left: none !important; 
        padding-left: 5px;
        /* Force remove any default blue border on hover/focus */
        outline: none !important;
    }

    /* Keep icon and input background consistent, even with browser autofill */
    input.form-control:-webkit-autofill,
    input.form-control:-webkit-autofill:hover,
    input.form-control:-webkit-autofill:focus,
    input.form-control:-webkit-autofill:active {
        -webkit-text-fill-color: #212529;
        -webkit-box-shadow: 0 0 0px 1000px #ffffff inset;
        box-shadow: 0 0 0px 1000px #ffffff inset;
        transition: background-color 5000s ease-in-out 0s;
        caret-color: #212529;
    }

    /* 2. REMOVE BLUE COMPLETELY & ADD GREEN HOVER */
    /* Target the group border on hover */
    .input-group:hover .input-group-text,
    .input-group:hover .form-control {
        border-color: #28a745 !important;
    }

    /* Target the individual input focus to kill the blue */
    .form-control:focus, 
    .form-control:active {
        border-color: #dee2e6 !important; /* Keep neutral or set to green */
        box-shadow: none !important;
        outline: 0 none !important;
    }

    /* 3. GREEN FOCUS WITHIN: The professional look */
    /* This handles the glow for the entire group without any blue */
    .input-group:focus-within {
        border-radius: 0.375rem;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }
    
    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control {
        border-color: #28a745 !important;
    }

    /* Login Button */
    .btn-login {
        background-color: #28a745 !important;
        border: none !important;
        color: white !important;
        transition: 0.2s ease-in-out;
    }
    .btn-login:hover {
        background-color: #218838 !important;
        opacity: 0.9;
    }

</style>

<div class="vh-100 d-flex align-items-center justify-content-center overflow-hidden" 
     style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('<?php echo htmlspecialchars(
         app_url('assets/images/login-bg.png'),
     ); ?>'); 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat;">
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                
                <div class="card border-0 shadow-lg login-card-elevated animate-fade-in" 
                    style="border-radius: 1rem; min-height: 390px;">
                    
                    <div class="card-body p-3 p-md-4">
                        <div class="text-center mb-4">
                            <img
                                class="img-fluid"
                                style="max-height: 64px;"
                                src="<?php echo htmlspecialchars(app_url('assets/images/nidec-logo.png')); ?>"
                                alt="Nidec Logo"
                            />
                            <div class="small text-muted mt-2" style="letter-spacing: .2em; text-transform: uppercase; font-size: 0.75rem;">
                                Philippines Corporation
                            </div>
                            <h1 class="h5 fw-bold mt-2 mb-0" style="color: #333;">
                                SECURITY REPORTING SYSTEM
                            </h1>
                        </div>

                        <?php if (isset($error) && $error !== null): ?>
                        <div class="alert alert-danger mb-4 py-2 small" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <div id="alert-box" class="alert alert-danger alert-error mb-4 hidden" role="alert"></div>

                        <form method="POST" action="" id="login-form">

                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold d-block custom-label">Username</label>
                                <div class="input-group" style="width: 90%; margin: 0 auto;">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                        placeholder="Enter your username" autocomplete="off" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold d-block custom-label">Password</label>
                                <div class="input-group" style="width: 90%; margin: 0 auto;">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                        placeholder="Enter your password" />
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login fw-bold py-2" 
                                    style="width: 45%; display: block; margin: 0 auto; border-radius: 50px;"> 
                                LOGIN
                            </button>
                        </form>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username) {
                e.preventDefault();
                alert('Please enter your username.'); // Using standard JS alert as requested
                return;
            }
            if (!password) {
                e.preventDefault();
                alert('Please enter your password.');
                return;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
