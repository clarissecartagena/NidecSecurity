<?php
// Sidebar component
// Expects: $currentPage, $user, $navItems, $icons from config.php
$constantsPath = __DIR__ . '/../../config/constants.php';
if ((!isset($navItems) || !is_array($navItems) || !isset($icons) || !is_array($icons)) && is_file($constantsPath)) {
    require $constantsPath;
}

$navItems = isset($navItems) && is_array($navItems) ? $navItems : [];
$icons = isset($icons) && is_array($icons) ? $icons : [];
$userRole = $user['role'] ?? '';
?>
<aside
    id="appSidebar"
    class="sidebar offcanvas-md offcanvas-start"
    tabindex="-1"
    aria-label="Sidebar"
    style="--bs-offcanvas-bg: hsl(var(--sidebar-background)); --bs-offcanvas-color: hsl(var(--sidebar-foreground));"
>
    <div class="offcanvas-header d-md-none">
        <h5 class="offcanvas-title m-0">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column">
        <div class="sidebar-header">
            <div class="sidebar-brand d-flex align-items-center justify-content-center">
                <img
                    class="sidebar-brand-logo"
                    src="<?php echo htmlspecialchars(app_url('assets/images/nidec-logo2.png')); ?>"
                    alt="Nidec"
                />
            </div>
        </div>

        <nav class="sidebar-nav nav nav-pills flex-column gap-1 p-2">
            <?php foreach ($navItems as $item): ?>
                <?php if (in_array($userRole, $item['roles'])): ?>
                    <a
                        href="<?php echo htmlspecialchars(app_url($item['path'])); ?>"
                        class="nav-link d-flex align-items-center gap-2 <?php echo $currentPage === $item['path']
                            ? 'active'
                            : ''; ?>"
                        title="<?php echo htmlspecialchars($item['label']); ?>"
                    >
                        <span class="sidebar-link-icon" aria-hidden="true"><?php echo $icons[$item['icon']] ??
                            ''; ?></span>
                        <span class="sidebar-link-label"><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer mt-auto">
            <div class="sidebar-collapse-control p-2">
                <button
                    id="sidebar-collapse-toggle"
                    class="btn btn-outline-light w-100 d-none d-md-inline-flex align-items-center justify-content-center"
                    type="button"
                    aria-label="Collapse sidebar"
                    aria-pressed="false"
                    title="Collapse sidebar"
                >
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</aside>

