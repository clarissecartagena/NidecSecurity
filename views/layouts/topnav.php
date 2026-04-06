<?php
// Top Navigation component
// Expects: $user from config.php
?>
<header class="topnav navbar navbar-expand bg-body border-bottom">
    <div class="container-fluid gap-2">
        <div class="d-flex align-items-center gap-2">
            <!-- Mobile sidebar toggle -->
            <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Toggle sidebar">
                <i class="bi bi-list" style="font-size:1.2rem;" aria-hidden="true"></i>
            </button>

            <span class="navbar-text text-muted fw-semibold" style="font-size:0.9rem;">
                Security Reporting System
            </span>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">

            <!-- Notifications Bell -->
            <div class="position-relative">
                <button id="notifications-bell" type="button" onclick="if (window.UI && typeof UI.toggleNotifications === 'function') { UI.toggleNotifications(); } else { window.location.href = '<?php echo htmlspecialchars(
                    app_url('notifications.php'),
                ); ?>'; }"
                        class="topnav-icon-btn" title="Notifications" aria-label="Notifications">
                    <i class="bi bi-bell-fill" aria-hidden="true"></i>
                    <span id="notification-badge"
                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                          style="display:none; font-size:0.6rem; background:hsl(var(--primary)); color:#fff; min-width:1.25rem; text-align:center;">0</span>
                </button>

                <div id="notifications-dropdown" class="notifications-dropdown dropdown-menu dropdown-menu-end p-0 hidden" style="min-width: 320px;">
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <h6 class="m-0">Notifications</h6>
                        <button id="notifications-mark-all" type="button" class="btn btn-link btn-sm text-decoration-none p-0">Mark all read</button>
                    </div>
                    <div class="px-3 py-2 border-bottom">
                        <a href="<?php echo htmlspecialchars(
                            app_url('notifications.php'),
                        ); ?>" class="small text-muted text-decoration-none">View all notifications →</a>
                    </div>
                    <div id="notifications-list" class="notifications-list"></div>
                </div>
            </div>

            <!-- Divider -->
            <div style="width:1px; height:22px; background:#e5e7eb; margin:0 6px;"></div>

            <!-- User Profile -->
            <div class="d-flex align-items-center gap-2 px-2">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:34px; height:34px;">
                    <i class="bi bi-person-fill text-white" style="font-size:1rem;" aria-hidden="true"></i>
                </div>
                <div class="d-none d-md-block" style="line-height:1.2;">
                    <div id="topnav-username" class="fw-bold text-foreground" style="font-size:0.85rem;"><?php echo htmlspecialchars(
                        $user['username'] ?? 'Admin',
                    ); ?></div>
                    <div id="topnav-role" class="text-muted" style="font-size:0.73rem;"><?php echo htmlspecialchars(
                        $user['displayName'] ?? '',
                    ); ?></div>
                </div>
            </div>

            <!-- Divider -->
            <div style="width:1px; height:22px; background:#e5e7eb; margin:0 6px;"></div>

            <!-- Logout -->
            <a href="<?php echo htmlspecialchars(
                app_url('logout.php'),
            ); ?>" onclick="if (window.Auth && typeof Auth.logout === 'function') { Auth.logout(); return false; }"
               class="topnav-icon-btn topnav-logout-btn" title="Sign out" aria-label="Sign out">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            </a>

        </div>
    </div>
</header>

<style>
    /* ── Topnav icon buttons ── */
    .topnav-icon-btn {
        background: transparent;
        border: none;
        border-radius: 8px;
        width: 38px; height: 38px;
        display: flex; align-items: center; justify-content: center;
        color: #6b7280;
        font-size: 1.2rem;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
        position: relative;
        padding: 0;
        flex-shrink: 0;
    }
    .topnav-icon-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }
    /* Red logout */
    .topnav-logout-btn { color: #dc2626; }
    .topnav-logout-btn:hover {
        background: #fff1f2;
        color: #b91c1c;
    }
</style>
